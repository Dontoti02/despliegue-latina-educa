<?php

namespace Modules\Tenant\Packages\Content\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Enum\EmailBodyTemplate;
use Modules\Shared\Services\MailerService;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\Content\Helpers\ContentHelper;
use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\Average;
use Modules\Tenant\Models\AverageDetail;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Models\ContentGroup;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Packages\EvaluationForm\Repositories\EvaluationFormRepository;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Packages\Link\Repositories\LinkRepository;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Answer\Enums\AnswerStatus;
use Modules\Tenant\Packages\Answer\Repositories\AnswerRepository;
use Modules\Tenant\Packages\Comment\Repositories\CommentRepository;
use Modules\Tenant\Services\FileTenantService;

class ContentRepository
{
    public static function list(int $classroom_id)
    {
        $user = User::authenticated();

        $is_student = $user->rol_id === RolTenant::STUDENT;

        $classroom = Classroom::findOrFail($classroom_id);

        $syllabus = ContentGroup::firstOrCreate([
            'title' => 'Sílabo',
            'classroom_id' => $classroom_id,
        ]);

        $syllabusMap = [
            ...$syllabus->only(['id', 'title']),
            'files' => FileRepository::listByModel($syllabus),
        ];

        $contentGroups = ContentGroup::select()
            ->where('classroom_id', $classroom_id)
            ->whereNot('title', 'Sílabo')
            ->get();

        $contentGroupsMap = [];
        foreach ($contentGroups as $contentGroup) {

            $contents = $contentGroup->contents()
                ->select([
                    'id',
                    'title',
                    'created_at as date',
                    'type',
                    'is_visible',
                    'date_start',
                    'date_limit',
                    'is_open',
                ])->get();

            $contentsMap = [];
            foreach ($contents as $content) {
                self::manageStates($content);

                if ($is_student && !$content->is_visible) {
                    continue;
                }

                $contentsMap[] = $content;
            }

            if ($is_student && !count($contentsMap)) {
                continue;
            }

            $contentGroupsMap[] = [
                'id' => $contentGroup->id,
                'title' => $contentGroup->title,
                'contents' => $contentsMap,
            ];
        }

        $evaluation_groups = $classroom->evaluationGroups()->count();

        $hasCompetencies = $evaluation_groups > 0 ? true : false;

        $result = [
            'syllabus' => $syllabusMap,
            'content_groups' => $contentGroupsMap,
            'hasCompetencies' => $hasCompetencies
        ];

        return $result;
    }

    public static function detail(int $id)
    {
        $user = User::authenticated();

        $content = Content::findOrFail($id);
        $classroom_id = $content->contentGroup->classroom_id;

        self::manageStates($content);

        if ($user->rol_id == RolTenant::TEACHER) {

            ClassroomHelper::validateAccess($classroom_id, 'teacher');

            $classroom = Classroom::findOrFail($classroom_id);

            $evaluation_groups = $classroom->evaluationGroups()->count();

            $hasCompetencies = $evaluation_groups > 0 ? true : false;

            $content = self::get($content->id);

            return [
                ...$content,
                'hasCompetencies' => $hasCompetencies
            ];
        }

        ClassroomHelper::validateAccess($classroom_id, 'student');

        $content = self::get($content->id, $user->person_id);

        return $content;
    }

    public static function get(int $id, $person_id = null)
    {
        $content = Content::findOrFail($id);

        $result = [
            'id' => $content->id,
            'content_group_id' => $content->content_group_id,
            'evaluation_group_id' => $content->evaluation_group_id,
            'title' => $content->title,
            'description' => $content->description,
            'date' => $content->created_at,
            'type' => $content->type,
            'is_visible' => $content->is_visible,
            'date_start' => $content->date_start,
            'date_limit' => $content->date_limit,
            'is_open' => $content->is_open,
            'score' => $content->score,
            'has_evaluation_form' => $content->has_evaluation_form,
            'files' => FileRepository::listByModel($content),
            'links' => LinkRepository::listByModel($content),
            'comments' => CommentRepository::listByModel($content),
        ];

        if ($person_id) {
            $answer = $content->answers()
                ->where('answer.person_id', $person_id)
                ->first();

            $result['answer'] = AnswerRepository::get($answer->id);

            if ($content->has_evaluation_form) {
                $result['form_uuid'] = $content->form->uuid;
            }
        } else {
            if ($content->has_evaluation_form && $content->form) {
                $result['form'] = $content->form->load('questions');
            }
        }

        return $result;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        ContentHelper::validateRequest($request);
        ContentHelper::validateDateStartAndLimit($request);

        $classroom = ContentGroup::findOrFail($request->content_group_id)->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $content = Content::create([
            'content_group_id' => $request->content_group_id,
            'evaluation_group_id' => $request->evaluation_group_id ?? null,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'is_visible' => false,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'is_open' => false,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
        ]);

        $date = Carbon::now();
        $participants = Participant::where('classroom_id', $classroom->id)->get();

        $records = [];
        foreach ($participants as $participant) {
            $records[] = [
                'person_id' => $participant->person_id,
                'content_id' => $content->id,
                'status' => AnswerStatus::PENDING,
                'score' => 0,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        Answer::insert($records);

        if (in_array($request->type, ['task', 'evaluation'])) {

            $exists = Average::select()
                ->where('evaluation_group_id', $request->evaluation_group_id)
                ->exists();

            if (!$exists) {

                $records = [];
                foreach ($participants as $participant) {
                    $records[] = [
                        'person_id' => $participant->person_id,
                        'evaluation_group_id' => $request->evaluation_group_id,
                        'score' => 0,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                }

                Average::insert($records);
            }

            $exists = AverageDetail::select()
                ->where('content_group_id', $request->content_group_id)
                ->where('evaluation_group_id', $request->evaluation_group_id)
                ->exists();

            if (!$exists) {

                $records = [];
                foreach ($participants as $participant) {
                    $records[] = [
                        'person_id' => $participant->person_id,
                        'content_group_id' => $request->content_group_id,
                        'evaluation_group_id' => $request->evaluation_group_id,
                        'score' => 0,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];
                }

                AverageDetail::insert($records);
            }
        }

        if ($request->type == 'evaluation' && $request->has_evaluation_form) {
            EvaluationFormRepository::set($content, $request->form, false);
        }

        $result = self::get($content->id);

        $subject = 'Notificación de nuevo contenido';
        $body = EmailBodyTemplate::createContent;
        foreach ($participants as $participant) {
            self::sendMail([
                'subject' => $subject,
                'body' => $body,
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'courseName' => $content->contentGroup->classroom->studyPlanDetail->course->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $content = Content::findOrFail($id);

        ContentHelper::validateRequest($request, $id);
        ContentHelper::validateDateStartAndLimit($request, $content->date_start);

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $date_start = $request->date_start
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_start)
            : null;

        $date_limit = $request->date_limit
            ? Carbon::createFromFormat('Y-m-d H:i:s', $request->date_limit)
            : null;

        $hasEvaluationForm = $content->has_evaluation_form;

        $content->update([
            'content_group_id' => $request->content_group_id,
            'evaluation_group_id' => $request->evaluation_group_id ?? null,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'type' => $request->type,
            'date_start' => $date_start,
            'date_limit' => $date_limit,
            'score' => $request->score ?? null,
            'has_evaluation_form' => $request->has_evaluation_form ?? false,
        ]);

        if ($hasEvaluationForm && !$request->has_evaluation_form) {
            EvaluationFormRepository::delete($content);
        } else if (($hasEvaluationForm && $request->has_evaluation_form) || (!$hasEvaluationForm && $request->has_evaluation_form)) {
            EvaluationFormRepository::set($content, $request->form, $hasEvaluationForm == $request->has_evaluation_form);
        }

        $result = self::get($content->id);

        $participants = Participant::where('classroom_id', $classroom->id)->get();

        $subject = 'Notificación de contenido actualizado';
        $body = EmailBodyTemplate::updateContent;
        foreach ($participants as $participant) {
            self::sendMail([
                'name' => $participant->person->names,
                'email' => $participant->person->email,
                'subject' => $subject,
                'body' => $body,
                'courseName' => $content->contentGroup->classroom->studyPlanDetail->course->name,
                'teacherName' => $user->person->names,
            ]);
        }

        return $result;
    }

    public static function updateVisibility(int $id)
    {
        $content = Content::findOrFail($id);

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $is_visible = !$content->is_visible;
        $date_limit = $content->date_limit;
        $is_open = $content->is_open;

        if (!$is_visible) {
            $date = Carbon::now();
            $is_open = false;

            if ($date <= $date_limit) {
                $date_limit = $date;
            }
        }

        $content->update([
            'is_visible' => $is_visible,
            'date_limit' => $date_limit,
            'is_open' => $is_open,
        ]);

        return $is_visible;
    }

    public static function updateStatus(int $id)
    {
        $content = Content::findOrFail($id);

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $date_limit = $content->date_limit;
        $is_open = !$content->is_open;

        if (!$is_open) {
            $date = Carbon::now();

            if ($date <= $date_limit) {
                $date_limit = $date;
            }
        }

        $content->update([
            'date_limit' => $date_limit,
            'is_open' => $is_open,
        ]);

        return $is_open;
    }

    public static function delete(int $id)
    {
        $content = Content::findOrFail($id);

        $classroom = $content->contentGroup->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $files = $content->files;
        FileTenantService::delete($files->all());

        DB::beginTransaction();
        try {
            $content->links()->delete();
            $content->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return "Contenido eliminado correctamente";
    }

    private static function sendMail(array $info)
    {
        $info = (object) $info;

        $subject = $info->subject;
        $body = $info->body;

        $name = $info->name;
        $email = $info->email;
        $courseName = $info->courseName;
        $teacherName = $info->teacherName;

        $body = str_replace('{{name}}', $name, $body);
        $body = str_replace('{{courseName}}', $courseName, $body);
        $body = str_replace('{{teacherName}}', $teacherName, $body);

        $data = (object) [
            'subject' => $subject,
            'body' => $body,
            'to' => $email,
        ];

        MailerService::send($data);
    }

    public static function report(Request $request)
    {
        ContentHelper::validateReportRequest($request);

        $classroomId = $request->input('classroom_id');

        ClassroomHelper::validateAccess($classroomId);

        $classroom = Classroom::findOrFail($classroomId);

        $syllabus = $classroom->contentGroups()
            ->selectRaw("
                id,
                title as content_group_title,
                title,
                title as type,
                NULL as evaluation_group_title,
                created_at as date,
                NULL as date_limit,
                NULL as status
            ")
            ->where('title', 'Sílabo')
            ->get();

        $contents = Content::selectRaw("
                content.id,
                content_group.title as content_group_title,
                content.title,
                CASE
                    WHEN content.type = 'task' THEN 'Tarea'
                    WHEN content.type = 'evaluation' THEN 'Evaluación'
                    ELSE 'Contenido'
                END as type,
                evaluation_group.title as evaluation_group_title,
                content.created_at as date,
                content.date_limit,
                CASE WHEN content.is_visible = 1 THEN 'Visible' ELSE 'No visible' END as status
            ")
            ->join('content_group', 'content.content_group_id', 'content_group.id')
            ->leftJoin('evaluation_group', 'content.evaluation_group_id', 'evaluation_group.id')
            ->where('content_group.classroom_id', $classroomId)
            ->whereNot('content_group.title', 'Sílabo')
            ->orderBy('content_group.id', 'asc')
            ->orderBy('content.created_at', 'asc')
            ->get();

        $result = [
            ...$syllabus->toArray(),
            ...$contents->toArray(),
        ];

        return [$result, $classroom];
    }

    public static function manageStates(Content $content)
    {
        if ($content->date_start && $content->date_limit) {
            $date = Carbon::now();

            $in_range = (bool) ($date >= $content->date_start && $date <= $content->date_limit);

            if ($content->is_open != $in_range || (!$content->is_visible && $in_range)) {
                $content->update([
                    'is_visible' => (bool) $content->is_visible || (!$content->is_visible && $in_range),
                    'is_open' => $in_range,
                ]);
            }
        }
    }
}
