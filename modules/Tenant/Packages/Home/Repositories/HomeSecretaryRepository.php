<?php

namespace Modules\Tenant\Packages\Home\Repositories;

use Modules\Tenant\Models\Assistance;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class HomeSecretaryRepository
{
    public static function home()
    {
        User::authenticated([RolTenant::ACADEMIC_SECRETARY, RolTenant::ADMINISTRADOR]);

        $period = PeriodHelper::current();

        $alerts = [];

        $totalClassrooms = 0;
        $totalStudents = 0;
        $totalTeachers = 0;

        $studyPrograms = [];

        if ($period) {
            $study_programs = self::getStudyPrograms($period->id);
            $classrooms = self::getClassrooms($period->id);
            $students = self::getStudents($period->id);
            $teachers = self::getTeachers($period->id);

            $totalClassrooms = $classrooms->total;
            $totalStudents = $students->total;
            $totalTeachers = $teachers->total;

            $studyPrograms = $study_programs->items;
        } else {
            $alerts = ['imports' => SystemConfigurationHelper::getAlertsImports()];
        }

        $summary = [
            [
                'title' => 'Unidades Didácticas activas',
                'value' => $totalClassrooms,
            ],
            [
                'title' => 'Alumnos inscritos',
                'value' => $totalStudents,
            ],
            [
                'title' => 'Profesores inscritos',
                'value' => $totalTeachers,
            ],
            [
                'title' => 'Periodo Lectivo actual',
                'value' => $period ? $period->name : 'Sin periodo lectivo',
            ],
        ];

        $result = [
            'alerts' => $alerts,
            'summary' => $summary,
            'study_programs' => $studyPrograms,
        ];

        return $result;
    }

    public static function getStudyPrograms(int $periodId)
    {
        $studyPrograms = StudyProgram::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $studyProgramsMap = [];
        foreach ($studyPrograms as $studyProgram) {
            $classroomIds = Classroom::select()
                ->where('period_id', $periodId)
                ->whereHas('studyPlanDetail', function ($query) use ($studyProgram) {
                    $query->whereHas('studyPlan', function ($subquery) use ($studyProgram) {
                        $subquery->where('study_program_id', $studyProgram->id);
                    });
                })
                ->pluck('id');

            $totalClassrooms = $classroomIds->count();

            $classrooms = Classroom::selectRaw("
                classroom.*,
                (
                    SELECT COUNT(*) 
                    FROM participant p
                    WHERE p.classroom_id = classroom.id
                ) as students
            ")
                ->whereIn('id', $classroomIds)
                ->orderBy('students', 'desc')
                ->limit(5)
                ->get();

            $topClassrooms = [];
            foreach ($classrooms as $classroom) {
                $assistants = Assistance::selectRaw("
                    DATE(created_at) as date,
                    COUNT(*) as count
                ")
                    ->where('classroom_id', $classroom->id)
                    ->whereIn('status', ['attended', 'late'])
                    ->groupByRaw('DATE(created_at)')
                    ->orderByRaw('DATE(created_at) desc')
                    ->limit(5)
                    ->get();

                $topClassrooms[] = [
                    'id' => $classroom->id,
                    'name' => $classroom->studyPlanDetail->course->name,
                    'teacher' => $classroom->teacher ? $classroom->teacher->person->names : 'Sin asignar',
                    'students' => $classroom->students,
                    'assistants' => $assistants,
                ];
            }

            $studyProgramsMap[] = [
                'id' => $studyProgram->id,
                'name' => $studyProgram->name,
                'total_classrooms' => $totalClassrooms,
                'top_classrooms' => $topClassrooms,
            ];
        }

        $result = (object) [
            'items' => $studyProgramsMap,
        ];

        return $result;
    }

    public static function getClassrooms(int $periodId)
    {
        $totalClassrooms = Classroom::select()
            ->where('period_id', $periodId)
            ->count();

        $result = (object) [
            'total' => $totalClassrooms,
        ];

        return $result;
    }

    public static function getStudents(int $periodId)
    {
        $totalParticipants = Participant::select()
            ->whereHas('classroom', function ($classroom) use ($periodId) {
                $classroom->where('period_id', $periodId);
            })
            ->count();

        $result = (object) [
            'total' => $totalParticipants,
        ];

        return $result;
    }

    public static function getTeachers(int $periodId)
    {
        $totalTeachers = Teacher::select()
            ->whereHas('classrooms', function ($classrooms) use ($periodId) {
                $classrooms->where('period_id', $periodId);
            })
            ->count();

        $result = (object) [
            'total' => $totalTeachers,
        ];

        return $result;
    }
}
