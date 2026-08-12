<?php

namespace Modules\Tenant\Packages\AcademicHistory\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Packages\AcademicHistory\Helpers\AcademicHistoryHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Packages\AcademicHistory\Templates\HistoryTemplate;

class AcademicHistoryRepository
{
    public static function filters(int $studentId)
    {
        $user = User::authenticated();

        if ($user->rol_id === RolTenant::STUDENT) {
            $student = Student::select()
                ->where('person_id', $user->person_id)
                ->first();

            if (!$student) {
                throw new Exception('No se encontraron tus datos como estudiante.');
            }

            $studentId = $student->id;
        }

        Student::findOrFail($studentId);

        $studyPrograms = StudyProgram::select('id', 'name')
            ->whereHas('studyPlans.studentPlans', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('name', 'asc')
            ->get();

        $studyPlans = StudyPlan::select('id', 'name', 'study_program_id')
            ->whereHas('studentPlans', function ($query) use ($studentId) {
                $query->where('student_id', $studentId);
            })
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'study_programs' => $studyPrograms,
            'study_plans' => $studyPlans,
        ];

        return $result;
    }

    private static function generate(Request $request)
    {
        $user = User::authenticated();

        $isStudent = $user->rol_id == RolTenant::STUDENT;

        AcademicHistoryHelper::validateListRequest($request, $isStudent);

        $studentId = $request->input('student_id');
        $studyPlanId = $request->input('study_plan_id');

        if ($isStudent) {
            $student = Student::select()
                ->where('person_id', $user->person_id)
                ->first();

            if (!$student) {
                throw new Exception('No se encontraron tus datos como estudiante.');
            }

            $studentId = $student->id;
        }

        $student = Student::findOrFail($studentId);

        $studyPlan = StudyPlan::findOrFail($studyPlanId);

        $scoreMinToPass = $studyPlan->score_min_to_pass;

        $participants = Participant::select([
            'participant.*',
            'course.id as course_id',
            'course.name as course_name',
            'period.id as period_id',
            'period.name as period_name',
        ])
            ->join('classroom', function ($join) {
                $join
                    ->on('participant.classroom_id', '=', 'classroom.id')
                    ->whereNull('classroom.deleted_at');
            })
            ->join('period', function ($join) {
                $join
                    ->on('classroom.period_id', '=', 'period.id')
                    ->whereNull('period.deleted_at');
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
                // TODO: Evitamos filtrar por plan ya que el estudiante puede estar inscrito en cursos de diferentes planes.
                // ->where('study_plan_detail.study_plan_id', $studyPlanId); 
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
            ->where('participant.person_id', $student->person_id)
            ->orderBy('period.name', 'asc')
            ->orderBy('course.name', 'asc')
            ->get();

        $periods = $participants
            ->unique(fn($item) => $item->period_id)
            ->values();

        $accumulatedAverage = 0;

        $periodsMap = [];
        foreach ($periods as $indexPeriod => $period) {
            $courses = $participants
                ->where('period_id', $period->period_id)
                ->values();

            $coursesMap = [];
            foreach ($courses as $indexCourse => $course) {
                $coursesMap[] = [
                    'id' => $indexCourse + 1,
                    'name' => $course->course_name,
                    'credits' => $course->credits,
                    'score' => $course->score,
                    'is_approved' => $course->score >= $scoreMinToPass,
                ];
            }

            $scoreSum = $courses->sum('score');
            $scoreCount = $courses->count();

            $semesterAverage = $scoreCount > 0 ? $scoreSum / $scoreCount : 0;
            $accumulatedAverage = ($accumulatedAverage + $semesterAverage) / ($indexPeriod + 1);

            $periodsMap[] = [
                'id' => $period->period_id,
                'name' => $period->period_name,
                'semester_average' => round($semesterAverage, 2),
                'accumulated_average' => round($accumulatedAverage, 2),
                'courses' => $coursesMap,
            ];
        }

        $result = (object) [
            'student' => (object) [
                'id' => $student->id,
                'names' => $student->person->names,
                'document_number' => $student->person->document_number,
            ],
            'periods' => $periodsMap,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $result = self::generate($request)->periods;

        return $result;
    }

    public static function download(string $type, Request $request)
    {
        if (!in_array($type, ['pdf', 'xlsx'])) {
            throw new Exception('El tipo de archivo no es válido.');
        }

        $result = self::generate($request);

        $title = 'HISTORIAL ACADÉMICO';

        $columns = [
            'id' => '#',
            'name' => 'CURSO',
            'credits' => 'CRÉDITOS',
            'score' => 'NOTA',
        ];

        $columnsAligned = [
            'id',
            'credits',
            'score',
        ];

        $student = $result->student->names;
        $rows = $result->periods;

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionName = strtoupper($institutionName);

        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $applicationName = AdminSystemConfigurationHelper::getName();
        $applicationName = strtoupper($applicationName);

        $date = Carbon::now();
        $date = $date->isoFormat('dddd, D [de] MMMM [del] YYYY');

        $data = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'applicationName' => $applicationName,
            'title' => $title,
            'date' => $date,
            'student' => $student,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = HistoryTemplate::class;
        $view = 'exports/history';

        if ($type === 'pdf') {
            return Generate::generatePdf($view, $data);
        }

        if ($type === 'xlsx') {
            return Generate::generateXlsx($template, $data);
        }
    }
}
