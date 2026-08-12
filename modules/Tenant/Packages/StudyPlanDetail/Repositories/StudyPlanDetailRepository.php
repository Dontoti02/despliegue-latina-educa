<?php

namespace Modules\Tenant\Packages\StudyPlanDetail\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyPlanDetail;
use Modules\Tenant\Packages\StudyPlanDetail\Helpers\StudyPlanDetailHelper;

class StudyPlanDetailRepository
{
    public static function detail(int $studyPlanId)
    {
        StudyPlan::findOrFail($studyPlanId);

        $studyPlanDetails = StudyPlanDetail::select([
            'study_plan_detail.id',
            'study_plan_detail.cycle_id',
            'cycle.name as cycle_name',
            'study_plan_detail.course_id',
            'course.name as course_name',
        ])
            ->join('cycle', 'study_plan_detail.cycle_id', '=', 'cycle.id')
            ->whereNull('cycle.deleted_at')
            ->join('course', 'study_plan_detail.course_id', '=', 'course.id')
            ->whereNull('course.deleted_at')
            ->where('study_plan_detail.study_plan_id', $studyPlanId)
            ->orderBy('cycle.name', 'asc')
            ->orderBy('course.name', 'asc')
            ->get();

        $studyPlanDetailsMap = [];
        foreach ($studyPlanDetails as $detail) {
            $studyPlanDetailsMap[] = [
                'id' => $detail->id,
                'cycle_id' => $detail->cycle_id,
                'cycle_name' => $detail->cycle_name,
                'course_id' => $detail->course_id,
                'course_name' => $detail->course_name,
            ];
        }

        return $studyPlanDetailsMap;
    }

    public static function params(int $studyPlanId)
    {
        $studyPlan = StudyPlan::findOrFail($studyPlanId);

        $cycles = Cycle::select()
            ->whereDoesntHave('studyPlanDetails', function ($query) use ($studyPlanId) {
                $query->where('study_plan_id', $studyPlanId);
            })
            ->orderBy('name', 'asc')
            ->get();

        $courses = Course::select()
            ->whereDoesntHave('studyPlanDetails', function ($query) use ($studyPlanId) {
                $query->where('study_plan_id', $studyPlanId);
            })
            ->where(function ($query) use ($studyPlan) {
                $query
                    ->whereNull('study_program_id')
                    ->orWhere('study_program_id', $studyPlan->study_program_id);
            })
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'cycles' => $cycles,
            'courses' => $courses,
        ];

        return $result;
    }

    public static function assign(Request $request)
    {
        StudyPlanDetailHelper::validateRequest($request);

        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $courseId = $request->input('course_id');

        $existsStudyPlanDetail = StudyPlanDetail::select()
            ->where('study_plan_id', $studyPlanId)
            ->where('cycle_id', $cycleId)
            ->where('course_id', $courseId)
            ->first();

        if ($existsStudyPlanDetail) {
            throw new Exception("Ya existe una asignación para este plan de estudios, ciclo y unidad didáctica.");
        }

        $existsCourse = StudyPlanDetail::select()
            ->where('study_plan_id', $studyPlanId)
            ->where('course_id', $courseId)
            ->first();

        if ($existsCourse) {
            throw new Exception("La unidad didáctica ya está asignada a este plan de estudios en el ciclo {$existsCourse->cycle->name}.");
        }

        StudyPlanDetail::create([
            'study_plan_id' => $studyPlanId,
            'cycle_id' => $cycleId,
            'course_id' => $courseId,
        ]);

        return 'Asignación creada correctamente.';
    }

    public static function unassign(int $id)
    {
        $studyPlanDetail = StudyPlanDetail::findOrFail($id);

        if ($studyPlanDetail->classrooms()->exists()) {
            throw new Exception("No se puede eliminar esta asignación porque tiene clases asociadas.");
        }

        $studyPlanDetail->delete();

        return 'Asignación eliminada correctamente.';
    }
}
