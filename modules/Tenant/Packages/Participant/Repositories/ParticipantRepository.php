<?php

namespace Modules\Tenant\Packages\Participant\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Average;
use Modules\Tenant\Models\EvaluationGroup;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class ParticipantRepository
{
    public static function list(int $classroomId)
    {
        $user = User::authenticated();

        $isTeacher = $user->rol_id === RolTenant::TEACHER;

        if ($isTeacher) {
            $evaluationGroups = EvaluationGroup::select('id', 'title', 'weight')
                ->where('classroom_id', $classroomId)
                ->get();

            $result = Participant::select([
                'participant.id',
                'participant.person_id',
                'student.id as student_id',
                'person.names',
                'cycle.name as cycle',
                'participant.score',
                DB::raw("(
                    SELECT COUNT(*) 
                    FROM assistance
                    WHERE 
                        person_id = participant.person_id
                        AND classroom_id = participant.classroom_id
                        AND status = 'absence'
                ) as absences"),
            ])
                ->join('person', function ($join) {
                    $join
                        ->on('participant.person_id', '=', 'person.id')
                        ->whereNull('person.deleted_at');
                })
                ->join('student', function ($join) {
                    $join
                        ->on('participant.person_id', '=', 'student.person_id')
                        ->whereNull('student.deleted_at');
                })
                ->join('classroom', function ($join) use ($classroomId) {
                    $join
                        ->on('participant.classroom_id', '=', 'classroom.id')
                        ->whereNull('classroom.deleted_at')
                        ->where('classroom.id', $classroomId);
                })
                ->join('study_plan_detail', function ($join) {
                    $join
                        ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                        ->whereNull('study_plan_detail.deleted_at');
                })
                ->join('cycle', function ($join) {
                    $join
                        ->on('study_plan_detail.cycle_id', '=', 'cycle.id')
                        ->whereNull('cycle.deleted_at');
                })
                ->join('course', function ($join) {
                    $join
                        ->on('study_plan_detail.course_id', '=', 'course.id')
                        ->whereNull('course.deleted_at');
                })
                ->orderBy('person.names', 'asc')
                ->get();

            $participantIds = $result->pluck('person_id');

            $averages = Average::whereIn('person_id', $participantIds)
                ->select('person_id', 'evaluation_group_id', 'score')
                ->get();

            $details = [];
            foreach ($averages as $average) {
                if (!isset($details[$average->person_id])) {
                    $details[$average->person_id] = [];
                }
                $details[$average->person_id][$average->evaluation_group_id] = $average->score;
            }

            $result->transform(function ($participant) use ($details, $evaluationGroups) {
                $participantDetails = isset($details[$participant->person_id]) ? $details[$participant->person_id] : [];

                foreach ($evaluationGroups as $group) {
                    $score = $participantDetails[$group->id] ?? 0;
                    $participant['evaluation_' . $group->id] = $score;
                }

                return $participant;
            });


            return [
                'result' => $result,
                'evaluationGroups' => $evaluationGroups
            ];
        }

        $teachers = Teacher::select([
            'teacher.id',
            'teacher.person_id',
            'person.names',
            'user.email',
            'user.last_login',
        ])
            ->join('person', function ($join) {
                $join
                    ->on('teacher.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->join('user', function ($join) {
                $join
                    ->on('person.id', '=', 'user.person_id')
                    ->whereNull('user.deleted_at');
            })
            ->join('classroom', function ($join) use ($classroomId) {
                $join
                    ->on('teacher.id', '=', 'classroom.teacher_id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.id', $classroomId);
            })
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = Participant::select([
            'participant.id',
            'participant.person_id',
            'student.id as student_id',
            'person.names',
            'user.email',
            'user.last_login',
        ])
            ->join('person', function ($join) {
                $join
                    ->on('participant.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->join('student', function ($join) {
                $join
                    ->on('participant.person_id', '=', 'student.person_id')
                    ->whereNull('student.deleted_at');
            })
            ->join('user', function ($join) {
                $join
                    ->on('person.id', '=', 'user.person_id')
                    ->whereNull('user.deleted_at');
            })
            ->join('classroom', function ($join) use ($classroomId) {
                $join
                    ->on('participant.classroom_id', '=', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.id', $classroomId);
            })
            ->orderBy('person.names', 'asc')
            ->get();

        $result = [
            'teachers' => $teachers,
            'participants' => $participants,
        ];

        return $result;
    }
}
