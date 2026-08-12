<?php

namespace Modules\Tenant\Packages\Evaluation\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Answer\Enums\AnswerStatus;
use Modules\Tenant\Packages\Average\Repositories\AverageRepository;
use Modules\Tenant\Packages\Evaluation\Helpers\EvaluationHelper;

class EvaluationRepository
{
    public static function list(int $classroom_id, int $person_id)
    {
        $user = User::authenticated();

        $is_student = $user->rol_id === RolTenant::STUDENT;

        $classroom = Classroom::findOrFail($classroom_id);

        if (!$is_student) {
            if ($person_id === 0) {
                throw new Exception('El parámetro person_id es requerido');
            }

            $user = User::byKey('person_id', $person_id);
        }

        $participant = Participant::select()
            ->where('person_id', $user->person_id)
            ->where('classroom_id', $classroom_id)
            ->first();

        if (!$participant) {
            throw new Exception($is_student ? "No eres estudiante de esta clase" : "El estudiante no pertenece a esta clase");
        }

        $final_note = (float) $participant->score;

        $status = $final_note >= 11 ? 'Aprobado' : 'No aprobado';

        $next_evaluation = $classroom->contentGroups()
            ->select([
                'content.id',
                'content.title',
                'content.date_start',
                'content.date_limit',
            ])
            ->join('content', 'content_group.id', 'content.content_group_id')
            ->whereIn('content.type', ['task', 'evaluation'])
            ->where('content.date_start', '>=', Carbon::now())
            ->orderBy('content.date_start', 'asc')
            ->first();

        $content_groups = $classroom->contentGroups()
            ->select('id', 'title')
            ->whereNot('title', 'Sílabo')
            ->get()
            ->each(function ($content_group) use ($user, $classroom) {
                $content_group->evaluations = $content_group->contents()
                    ->select([
                        'content.id',
                        'content.title',
                        'content.type',
                        'content.created_at as date',
                        'content.date_start',
                        'content.date_limit',
                        'evaluation_group.title as evaluation_group',
                        'answer.score',
                    ])
                    ->join('evaluation_group', 'content.evaluation_group_id', 'evaluation_group.id')
                    ->join('answer', 'content.id', 'answer.content_id')
                    ->whereIn('content.type', ['task', 'evaluation'])
                    ->where('answer.person_id', $user->person_id)
                    ->get()
                    ->each(function ($evaluation) {
                        $evaluation->score = (float) $evaluation->score;
                    });

                $content_group->evaluation_groups = AverageRepository::list($classroom->id, $user->person_id, $content_group->id);
            });

        $result = [
            'status' => $status,
            'final_note' => $final_note,
            'next_evaluation' => $next_evaluation,
            'content_groups' => $content_groups,
        ];

        return $result;
    }

    public static function evaluate(Request $request)
    {
        EvaluationHelper::validateRequest($request);

        $answer = Answer::find($request->answer_id);
        $content = $answer->content;

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if ($content->date_limit && Carbon::now() < $content->date_limit) {
            throw new Exception('La tarea o evaluación aún no ha finalizado');
        }

        $content->update([
            'is_open' => false,
        ]);

        if ($request->score > $content->score) {
            throw new Exception('La nota no puede ser mayor a la establecida en la tarea o evaluación');
        }

        $answer->update([
            'status' => AnswerStatus::EVALUATED,
            'score' => $request->score,
        ]);

        AverageRepository::calculateAverageDetail($classroom->id, $answer->person_id);

        $result = [
            'final_note' => AverageRepository::getFinalNote($classroom->id, $answer->person_id),
        ];

        return $result;
    }

    public static function finalNotes(Request $request)
    {
        EvaluationHelper::validateFinalNotesRequest($request);

        ClassroomHelper::validateAccess($request->classroom_id, 'academic_secretary,administrador,teacher');

        $result = Participant::selectRaw("
            person.id,
            person.document_number,
            person.names,
            CAST(participant.score AS FLOAT) as score
       ")
            ->join('person', function ($join) {
                $join
                    ->on('participant.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->where('participant.classroom_id', $request->classroom_id)
            ->orderBy('person.names', 'asc')
            ->get();

        foreach ($result as $participant) {
            $names = explode(' ', $participant->names);
            $firstTwo = implode(' ', array_slice($names, 0, 2));
            $rest = implode(' ', array_slice($names, 2));
            $rest = ucwords(strtolower($rest));
            $participant->names = "$firstTwo, $rest";
        }

        return $result;
    }
}
