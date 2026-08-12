<?php

namespace Modules\Tenant\Packages\Home\Repositories;

use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\Assistance;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Models\Publication;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Models\Schedule;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Content\Repositories\ContentRepository;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class HomeStudentRepository
{
    public static function home()
    {
        $user = User::authenticated(RolTenant::STUDENT);

        $period = PeriodHelper::current();

        $classrooms = self::getClassrooms($period->id, $user->person_id);
        $alerts = self::getAlerts($period->id, $user->person_id);
        $answers = self::getAnswers($period->id, $user->person_id);
        $assistants = self::getAssistants($period->id, $user->person_id);
        $publications = self::getPublications($period->id, $user->person_id);
        $schedule = self::getSchedule($period->id, $user->person_id);

        $summary = [
            [
                'title' => 'Unidades Didácticas inscritas',
                'value' => $classrooms->total,
            ],
            [
                'title' => 'Tareas presentadas',
                'value' => $answers->totalPresented,
            ],
            [
                'title' => 'Publicaciones',
                'value' => $publications->total,
            ],
            [
                'title' => 'Tardanzas',
                'value' => $assistants->totalLate,
            ],
        ];

        $result = [
            'alerts' => $alerts,
            'summary' => $summary,
            'courses' => $classrooms->items,
            'tasks' => $answers->tasks,
            'assistants' => $assistants->items,
            'publications' => $publications->items,
            'schedule' => $schedule->items,
        ];

        return $result;
    }

    public static function getClassrooms(int $periodId, int $personId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('participants', function ($query) use ($personId) {
                $query->where('person_id', $personId);
            })
            ->get();

        $total = $classrooms->count();

        $classroomsMap = [];
        foreach ($classrooms as $classroom) {
            $classroomsMap[] = [
                'id' => $classroom->id,
                'name' => $classroom->studyPlanDetail->course->name,
                'teacher' => $classroom->teacher ? $classroom->teacher->person->names : 'Sin profesor',
            ];
        }

        usort($classroomsMap, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $result = (object) [
            'items' => $classroomsMap,
            'total' => $total,
        ];

        return $result;
    }

    public static function getAlerts(int $periodId, int $personId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('participants', function ($query) use ($personId) {
                $query->where('person_id', $personId);
            })
            ->get();

        $classroomsMap = [];
        foreach ($classrooms as $classroom) {
            $assistances = Assistance::select()
                ->where('person_id', $personId)
                ->where('classroom_id', $classroom->id)
                ->get();

            $total = $assistances->count();
            $absences = $assistances->where('status', 'absence')->count();

            $alertPercentage = $total ? $absences / $total : 0;

            if ($alertPercentage >= 0.1) {
                $classroomsMap[] = [
                    'id' => $classroom->id,
                    'course' => $classroom->studyPlanDetail->course->name,
                    'absences' => $absences,
                    'total' => $total,
                ];
            }
        }

        usort($classroomsMap, function ($a, $b) {
            return $b['absences'] <=> $a['absences'];
        });

        $result = [
            'classrooms' => $classroomsMap,
        ];

        return $result;
    }

    public static function getAnswers(int $periodId, int $personId)
    {
        $classroomIds = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('participants', function ($query) use ($personId) {
                $query->where('person_id', $personId);
            })
            ->pluck('id')
            ->toArray();

        $contents = Content::select()
            ->whereHas('contentGroup', function ($query) use ($classroomIds) {
                $query->whereIn('classroom_id', $classroomIds);
            })
            ->whereIn('type', ['task', 'evaluation'])
            ->get();

        foreach ($contents as $content) {
            ContentRepository::manageStates($content);
        }

        $contentsIds = $contents->pluck('id')->toArray();

        $query = Answer::select('answer.*')
            ->join('content', 'answer.content_id', 'content.id')
            ->whereNull('content.deleted_at')
            ->where('answer.person_id', $personId)
            ->whereIn('content.id', $contentsIds)
            ->where('content.is_visible', true);

        $resultMap = function (Answer $answer) {
            return [
                'id' => $answer->id,
                'content_id' => $answer->content_id,
                'classroom_id' => $answer->content->contentGroup->classroom_id,
                'course' => $answer->content->contentGroup->classroom->studyPlanDetail->course->name,
                'type' => $answer->content->type,
                'title' => $answer->content->title,
                'date_start' => $answer->content->date_start,
                'date_limit' => $answer->content->date_limit,
            ];
        };

        $answersPending = (clone $query)
            ->where('answer.status', 'pending')
            ->orderBy('content.date_start', 'asc')
            ->limit(3)
            ->get()
            ->map(fn($answer) => $resultMap($answer));

        $answersEvaluated = (clone $query)
            ->where('answer.status', 'evaluated')
            ->orderBy('content.date_start', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($answer) => $resultMap($answer));

        $totalPresented = (clone $query)
            ->whereIn('answer.status', ['delivered', 'overdue', 'evaluated'])
            ->count();

        $tasks = [
            'pending' => $answersPending,
            'evaluated' => $answersEvaluated,
        ];

        $result = (object) [
            'tasks' => $tasks,
            'totalPresented' => $totalPresented,
        ];

        return $result;
    }

    public static function getAssistants(int $periodId, int $personId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('participants', function ($query) use ($personId) {
                $query->where('person_id', $personId);
            })
            ->get();

        $getValueAndPercentage = function ($total, $value) {
            $result = [
                'value' => $value,
                'percentage' => $total > 0
                    ? round(($value * 100) / $total) . '%'
                    : '0%',
            ];

            return $result;
        };

        $classroomsMap = [];
        $totalLate = 0;
        foreach ($classrooms as $classroom) {
            $assistances = Assistance::select()
                ->where('person_id', $personId)
                ->where('classroom_id', $classroom->id)
                ->get();

            $total = $assistances->count();
            $attended = $assistances->where('status', 'attended')->count();
            $absences = $assistances->where('status', 'absence')->count();
            $late = $assistances->where('status', 'late')->count();

            $totalLate += $late;

            $classroomsMap[] = [
                'id' => $classroom->id,
                'course' => $classroom->studyPlanDetail->course->name,
                'total' => $total,
                'attended' => $getValueAndPercentage($total, $attended),
                'absence' => $getValueAndPercentage($total, $absences),
                'late' => $getValueAndPercentage($total, $late),
            ];
        }

        $result = (object) [
            'items' => $classroomsMap,
            'totalLate' => $totalLate,
        ];

        return $result;
    }

    public static function getPublications(int $periodId, int $personId)
    {
        $query = Publication::select()
            ->where('person_id', $personId)
            ->whereHas('classroom', function ($query) use ($periodId) {
                $query->where('period_id', $periodId);
            })
            ->orderBy('created_at', 'desc');

        $total = (clone $query)->count();
        $publications = (clone $query)->limit(3)->get();

        $publicationsMap = [];
        foreach ($publications as $publication) {
            $publicationsMap[] = [
                'id' => $publication->id,
                'person' => $publication->person->names,
                'photo' => $publication->person->user->avatar,
                'course' => $publication->classroom->studyPlanDetail->course->name,
                'date' => $publication->created_at,
                'value' => $publication->value,
                'files' => FileRepository::listByModel($publication),
            ];
        }

        $result = (object) [
            'items' => $publicationsMap,
            'total' => $total,
        ];

        return $result;
    }

    public static function getSchedule(int $periodId, int $personId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('participants', function ($query) use ($personId) {
                $query->where('person_id', $personId);
            })
            ->get();

        $schedules = [];
        foreach ($classrooms as $classroom) {
            $days = Schedule::select([
                'id',
                'day',
                'hour_start',
                'hour_end',
            ])
                ->where('classroom_id', $classroom->id)
                ->get();

            $schedules[] = [
                'id' => $classroom->id,
                'course' => [
                    'id' => $classroom->studyPlanDetail->course->id,
                    'name' => $classroom->studyPlanDetail->course->name,
                ],
                'cycle' => [
                    'id' => $classroom->studyPlanDetail->cycle->id,
                    'name' => $classroom->studyPlanDetail->cycle->name,
                ],
                'section' => [
                    'id' => $classroom->section->id,
                    'name' => $classroom->section->name,
                ],
                'days' => $days,
            ];
        }

        $result = (object) [
            'items' => $schedules,
        ];

        return $result;
    }
}
