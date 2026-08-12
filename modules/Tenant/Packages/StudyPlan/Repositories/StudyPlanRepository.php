<?php

namespace Modules\Tenant\Packages\StudyPlan\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Packages\StudyPlan\Helpers\StudyPlanHelper;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyPlanType;
use Modules\Tenant\Models\StudyProgram;

class StudyPlanRepository
{
    public static function params()
    {
        $studyPrograms = StudyProgram::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $studyPlanTypes = StudyPlanType::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'study_programs' => $studyPrograms,
            'types' => $studyPlanTypes,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        StudyPlanHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $studyProgramId = $request->input('study_program_id');
        $typeId = $request->input('type_id');

        $studyPlans = StudyPlan::select()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('name', 'like', "%$search%")
                        ->orWhere('year', 'like', "%$search%");
                });
            })
            ->when($studyProgramId, function ($query) use ($studyProgramId) {
                $query->where('study_program_id', $studyProgramId);
            })
            ->when($typeId, function ($query) use ($typeId) {
                $query->where('type_id', $typeId);
            })
            ->orderBy('year', 'desc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $studyPlans->total(),
            'items' => $studyPlans->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        StudyPlanHelper::validateRequest($request);

        $studyProgramId = $request->input('study_program_id');
        $typeId = $request->input('type_id');
        $name = $request->input('name');
        $year = $request->input('year');
        $scoreMinToPass = $request->input('score_min_to_pass_number');

        $existsStudyPlanName = StudyPlan::select()
            ->where('name', $name)
            ->where('study_program_id', $studyProgramId)
            ->exists();

        if ($existsStudyPlanName) {
            throw new Exception("Ya existe un plan de estudios con el mismo nombre para el programa de estudios.");
        }

        StudyPlan::create([
            'study_program_id' => $studyProgramId,
            'type_id' => $typeId,
            'name' => $name,
            'year' => $year,
            'score_min_to_pass' => $scoreMinToPass,
        ]);

        return 'Plan de estudios creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        StudyPlanHelper::validateRequest($request);

        $studyProgramId = $request->input('study_program_id');
        $typeId = $request->input('type_id');
        $name = $request->input('name');
        $year = $request->input('year');
        $scoreMinToPass = $request->input('score_min_to_pass');

        $studyPlan = StudyPlan::findOrFail($id);

        $existsStudyPlanName = StudyPlan::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->where('study_program_id', $studyProgramId)
            ->exists();

        if ($existsStudyPlanName) {
            throw new Exception("Ya existe un plan de estudios con el mismo nombre para el programa de estudios.");
        }

        if ($studyPlan->studyPlanDetails()->exists()) {
            throw new Exception("No se puede actualizar el plan de estudios porque tiene unidades didácticas asociadas.");
        }

        if ($studyPlan->studentPlans()->exists()) {
            throw new Exception("No se puede actualizar el plan de estudios porque tiene estudiantes asociados.");
        }

        $studyPlan->update([
            'study_program_id' => $studyProgramId,
            'type_id' => $typeId,
            'name' => $name,
            'year' => $year,
            'score_min_to_pass' => $scoreMinToPass,
        ]);

        return 'Plan de estudios actualizado correctamente.';
    }

    public static function toggle(int $id)
    {
        $studyPlan = StudyPlan::findOrFail($id);

        $isActive = !$studyPlan->is_active;

        if (!$isActive) {
            $existsClassroomInPeriodCurrent = Classroom::select()
                ->whereHas('period', function ($query) {
                    $query->where('is_current', true);
                })
                ->whereHas('studyPlanDetail', function ($query) use ($studyPlan) {
                    $query->where('study_program_id', $studyPlan->study_program_id);
                })
                ->exists();

            if ($existsClassroomInPeriodCurrent) {
                throw new Exception("No se puede desactivar el plan de estudios porque hay clases asociadas en el periodo lectivo actual.");
            }
        }

        $studyPlan->update([
            'is_active' => $isActive,
        ]);

        $message = $isActive ? 'Plan de estudios activado correctamente.' : 'Plan de estudios desactivado correctamente.';

        return $message;
    }

    public static function delete(int $id)
    {
        $studyPlan = StudyPlan::findOrFail($id);

        if ($studyPlan->is_active) {
            throw new Exception("No se puede eliminar un plan de estudios activo.");
        }

        if ($studyPlan->studyPlanDetails()->exists()) {
            throw new Exception("No se puede eliminar el plan de estudios porque tiene unidades didácticas asociadas.");
        }

        if ($studyPlan->studentPlans()->exists()) {
            throw new Exception('No se puede eliminar el plan de estudios porque tiene estudiantes asociados.');
        }

        $studyPlan->delete();

        return 'Plan de estudios eliminado correctamente.';
    }

    public static function curriculum(int $id)
    {
        StudyPlan::findOrFail($id);

        $cycles = Cycle::select('id', 'name')
            ->whereHas('studyPlanDetails', function ($query) use ($id) {
                $query->where('study_plan_id', $id);
            })
            ->orderBy('order', 'asc')
            ->get();

        $courses = Course::select([
            'course.id',
            'study_plan_detail.cycle_id',
            'course.name',
            'course_type.name as type_name',
            'course.credits',
            'course.hours',
            'course.is_active',
        ])
            ->join('study_plan_detail', function ($join) use ($id) {
                $join
                    ->on('course.id', '=', 'study_plan_detail.course_id')
                    ->whereNull('study_plan_detail.deleted_at')
                    ->where('study_plan_detail.study_plan_id', $id);
            })
            ->leftJoin('course_type', function ($join) {
                $join
                    ->on('course.type_id', '=', 'course_type.id')
                    ->whereNull('course_type.deleted_at');
            })
            ->orderBy('name', 'asc')
            ->get();

        $cyclesMap = [];
        foreach ($cycles as $cycle) {
            $coursesByCycle = $courses
                ->where('cycle_id', $cycle->id)
                ->values();

            $cyclesMap[] = [
                'id' => $cycle->id,
                'name' => $cycle->name,
                'courses' => $coursesByCycle,
            ];
        }

        return $cyclesMap;
    }
}
