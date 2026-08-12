<?php

namespace Modules\Tenant\Packages\Answer\Repositories;

use Carbon\Carbon;
use Exception;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Packages\Link\Repositories\LinkRepository;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Answer\Enums\AnswerStatus;
use Modules\Tenant\Packages\Average\Repositories\AverageRepository;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;

class AnswerRepository
{
    public static function list(int $content_id)
    {
        $content = Content::findOrFail($content_id);

        $classroom_id = $content->contentGroup->classroom_id;

        ClassroomHelper::validateAccess($classroom_id, 'teacher');

        $select = Answer::select([
            'answer.id',
            'person.id as person_id',
            'person.names as person',
            'user.avatar as photo',
            'answer.created_at as date',
            'answer.status',
            'answer.score',
        ])
            ->join('person', 'answer.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('answer.content_id', $content_id)
            ->whereIn('answer.status', [
                AnswerStatus::DELIVERED,
                AnswerStatus::OVERDUE,
                AnswerStatus::EVALUATED,
            ])
            ->orderBy('answer.updated_at', 'asc')
            ->get();

        $answers = [];
        foreach ($select as $item) {
            $answers[] = [
                'id' => $item->id,
                'person_id' => $item->person_id,
                'person' => $item->person,
                'photo' => $item->photo,
                'date' => $item->date,
                'status' => $item->status,
                'score' => $item->score ? (float) $item->score : null,
                'final_note' => AverageRepository::getFinalNote($classroom_id, $item->person_id),
                'files' => FileRepository::listByModel($item),
                'links' => LinkRepository::listByModel($item),
            ];
        }

        $result = [
            'id' => $content->id,
            'title' => $content->title,
            'score' => $content->score ? (float) $content->score : null,
            'has_evaluation_form' => $content->has_evaluation_form,
            'form_uuid' => $content->form->uuid ?? null,
            'answers' => $answers,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $answer = Answer::select('id', 'status', 'score')
            ->where('id', $id)
            ->first();

        $result = [
            'id' => $answer->id,
            'status' => $answer->status,
            'score' => $answer->score ? (float) $answer->score : null,
            'files' => FileRepository::listByModel($answer),
            'links' => LinkRepository::listByModel($answer),
        ];

        return $result;
    }

    public static function delivered(int $id)
    {
        $user = User::authenticated();

        $answer = Answer::findOrFail($id);
        $content = $answer->content;
        $classroom = $content->contentGroup->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if ($answer->status == AnswerStatus::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $date = Carbon::now();
        $date_start = Carbon::parse($content->date_start);
        $date_limit = Carbon::parse($content->date_limit);

        if (!$content->is_open && ($date < $date_start || $date > $date_limit)) {
            throw new Exception('La tarea no admite respuestas');
        }

        $status = $answer->status == AnswerStatus::PENDING
            ? ($date <= $date_limit ? AnswerStatus::DELIVERED : AnswerStatus::OVERDUE)
            : AnswerStatus::PENDING;

        $answer->update([
            'status' => $status,
        ]);

        if ($status != AnswerStatus::PENDING) {
            $subject = 'Notificación de respuesta entregada';
            $body = EmailBodyTemplate::deliveredAnswer;
            self::sendMail([
                'subject' => $subject,
                'body' => $body,
                'name' => $content->contentGroup->classroom->teacher->person->names,
                'email' => $content->contentGroup->classroom->teacher->person->email,
                'courseName' => $content->contentGroup->classroom->studyPlanDetail->course->name,
                'studentName' => $user->person->names,
            ]);
        }

        return $answer->status;
    }

    private static function sendMail(array $info)
    {
        $info = (object) $info;

        $subject = $info->subject;
        $body = $info->body;

        $name = $info->name;
        $email = $info->email;
        $courseName = $info->courseName;
        $studentName = $info->studentName;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{courseName}}', $courseName, $body);
        $body = str_replace('{{studentName}}', $studentName, $body);

        $data = (object) [
            'subject' => $subject,
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }
}
