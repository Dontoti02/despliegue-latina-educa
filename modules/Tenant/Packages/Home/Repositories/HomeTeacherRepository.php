<?php

namespace Modules\Tenant\Packages\Home\Repositories;

use Carbon\Carbon;
use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\Assistance;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Models\Publication;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Models\Schedule;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class HomeTeacherRepository
{
    public static function home()
    {
        $user = User::authenticated(RolTenant::TEACHER);

        $teacher = Teacher::select()
            ->where('person_id', $user->person_id)
            ->first();

        $period = PeriodHelper::current();

        $classrooms = self::getClassrooms($period->id, $teacher->id);
        $answers = self::getAnswers($period->id, $teacher->id);
        $assistants = self::getAssistants($period->id, $teacher->id);
        $publications = self::getPublications($period->id, $user->person_id);
        $schedule = self::getSchedule($period->id, $teacher->id);

        $summary = [
            [
                'title' => 'Unidades Didácticas a cargo',
                'value' => $classrooms->total,
            ],
            [
                'title' => 'Tareas evaluadas',
                'value' => $answers->totalEvaluated,
            ],
            [
                'title' => 'Publicaciones',
                'value' => $publications->total,
            ],
            [
                'title' => 'Horas de clase a la semana',
                'value' => $schedule->totalHours,
            ],
        ];

        $result = [
            'summary' => $summary,
            'courses' => $classrooms->items,
            'tasks' => $answers->tasks,
            'assistants' => $assistants->items,
            'publications' => $publications->items,
            'schedule' => $schedule->items,
        ];

        return $result;
    }

    public static function getClassrooms(int $periodId, int $teacherId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->where('teacher_id', $teacherId)
            ->get();

        $total = $classrooms->count();

        $classroomsMap = [];
        foreach ($classrooms as $classroom) {
            $classroomsMap[] = [
                'id' => $classroom->id,
                'name' => $classroom->studyPlanDetail->course->name,
                'period' => $classroom->period->name,
                'students' => $classroom->participants()->count(),
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

    public static function getAnswers(int $periodId, int $teacherId)
    {
        $classroomIds = Classroom::select()
            ->where('period_id', $periodId)
            ->where('teacher_id', $teacherId)
            ->pluck('id')
            ->toArray();

        $contentIds = Content::select()
            ->whereHas('contentGroup', function ($query) use ($classroomIds) {
                $query->whereIn('classroom_id', $classroomIds);
            })
            ->whereIn('type', ['task', 'evaluation'])
            ->pluck('id')
            ->toArray();

        $query = Answer::select('answer.*')
            ->join('content', 'answer.content_id', 'content.id')
            ->whereNull('content.deleted_at')
            ->whereIn('content.id', $contentIds)
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
            ->whereIn('answer.status', ['delivered', 'overdue'])
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

        $totalEvaluated = (clone $query)
            ->where('answer.status', 'evaluated')
            ->count();

        $tasks = [
            'pending' => $answersPending,
            'evaluated' => $answersEvaluated,
        ];

        $result = (object) [
            'tasks' => $tasks,
            'totalEvaluated' => $totalEvaluated,
        ];

        return $result;
    }

    public static function getAssistants(int $periodId, int $teacherId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->where('teacher_id', $teacherId)
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
        foreach ($classrooms as $classroom) {
            $assistances = Assistance::select()
                ->where('classroom_id', $classroom->id)
                ->get();

            $total = $assistances->count();
            $attended = $assistances->where('status', 'attended')->count();
            $absences = $assistances->where('status', 'absence')->count();
            $late = $assistances->where('status', 'late')->count();

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

    public static function getSchedule(int $periodId, int $teacherId)
    {
        $classrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->where('teacher_id', $teacherId)
            ->get();

        $schedules = [];
        $totalMinutes = 0;
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

            foreach ($days as $day) {
                $hourStart = Carbon::createFromFormat('H:i', $day->hour_start);
                $hourEnd = Carbon::createFromFormat('H:i', $day->hour_end);

                $minutes = $hourStart->diffInMinutes($hourEnd);
                $totalMinutes += $minutes;
            }
        }

        $totalHours = intdiv($totalMinutes, 60);
        $remainingMinutes = $totalMinutes % 60;

        $totalHoursString = "{$totalHours}h";
        $remainingMinutesString = $remainingMinutes > 0 ? "{$remainingMinutes}m" : '';

        $result = (object) [
            'items' => $schedules,
            'totalHours' => $totalHoursString . $remainingMinutesString,
        ];

        return $result;
    }
}
