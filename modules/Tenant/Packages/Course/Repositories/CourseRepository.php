<?php

namespace Modules\Tenant\Packages\Course\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Course\Helpers\CourseHelper;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\CourseType;
use Modules\Tenant\Models\Module;
use Modules\Tenant\Models\StudyProgram;

class CourseRepository
{
    public static function params()
    {
        $studyPrograms = StudyProgram::select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get();

        $modules = Module::select()
            ->orderBy('order', 'asc')
            ->get();

        $courseTypes = CourseType::select()
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'study_programs' => $studyPrograms,
            'modules' => $modules,
            'types' => $courseTypes,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        CourseHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $studyProgramId = $request->input('study_program_id');
        $moduleId = $request->input('module_id');
        $typeId = $request->input('type_id');

        $courses = Course::select()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $word =  trim($search);
                    $query
                        ->orWhere('code', 'like', "%$word%")
                        ->orWhere('name', 'like', "%$word%")
                        ->orWhere('year', 'like', "%$word%");
                });
            })
            ->when($studyProgramId, function ($query) use ($studyProgramId) {
                $query->where('study_program_id', $studyProgramId);
            })
            ->when($moduleId, function ($query) use ($moduleId) {
                $query->where('module_id', $moduleId);
            })
            ->when($typeId, function ($query) use ($typeId) {
                $query->where('type_id', $typeId);
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $courses->total(),
            'items' => $courses->items(),
        ];

        return $result;
    }

    public static function all(Request $request)
    {
        $search = $request->query('search');
        $courses = Course::select()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $word =  trim($search);
                    $query
                        ->orWhere('code', 'like', "%$word%")
                        ->orWhere('name', 'like', "%$word%")
                        ->orWhere('year', 'like', "%$word%");
                });
            })
            ->orderBy('name', 'asc')
            ->get(['id', 'code', 'name', 'year']);

        return $courses;
    }

    public static function create(Request $request)
    {
        CourseHelper::validateRequest($request);

        $studyProgramId = $request->input('study_program_id');
        $moduleId = $request->input('module_id');
        $typeId = $request->input('type_id');
        $code = $request->input('code');
        $name = $request->input('name');
        $year = $request->input('year');
        $credits = $request->input('credits');
        $hours = $request->input('hours');
        $description = $request->input('description');

        $existsCourseName = Course::select()
            ->where('name', $name)
            ->exists();

        if ($existsCourseName) {
            throw new Exception("Ya existe una unidad didáctica con el mismo nombre.");
        }

        $existsCourseCode = Course::select()
            ->where('code', $code)
            ->exists();

        if ($existsCourseCode) {
            throw new Exception("Ya existe una unidad didáctica con el mismo código.");
        }

        Course::create([
            'study_program_id' => $studyProgramId,
            'module_id' => $moduleId,
            'type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'year' => $year,
            'credits' => $credits,
            'hours' => $hours,
            'description' => $description,
        ]);

        return "Unidad Didáctica creada correctamente.";
    }

    public static function update(int $id, Request $request)
    {
        CourseHelper::validateRequest($request, $id);

        $studyProgramId = $request->input('study_program_id');
        $moduleId = $request->input('module_id');
        $typeId = $request->input('type_id');
        $code = $request->input('code');
        $name = $request->input('name');
        $year = $request->input('year');
        $credits = $request->input('credits');
        $hours = $request->input('hours');
        $description = $request->input('description');

        $course = Course::findOrFail($id);

        $existsCourseName = Course::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsCourseName) {
            throw new Exception("Ya existe una unidad didáctica con el mismo nombre.");
        }

        $existsCourseCode = Course::select()
            ->where('id', '!=', $id)
            ->where('code', $code)
            ->exists();

        if ($existsCourseCode) {
            throw new Exception("Ya existe una unidad didáctica con el mismo código.");
        }

        if ($course->studyPlanDetails()->whereHas('classrooms')->exists()) {
            throw new Exception("No se puede actualizar una unidad didáctica asociada a una clase.");
        }

        $course->update([
            'study_program_id' => $studyProgramId,
            'module_id' => $moduleId,
            'type_id' => $typeId,
            'code' => $code,
            'name' => $name,
            'year' => $year,
            'credits' => $credits,
            'hours' => $hours,
            'description' => $description,
        ]);

        return "Unidad Didáctica actualizada correctamente.";
    }

    public static function toggle(int $id)
    {
        $course = Course::findOrFail($id);

        $is_active = !$course->is_active;

        $course->update([
            'is_active' => $is_active
        ]);

        $message = $is_active ? 'activado' : 'desactivado';

        return "Unidad Didáctica $message correctamente.";
    }

    public static function delete(int $id)
    {
        $course = Course::findOrFail($id);

        if ($course->is_active) {
            throw new Exception("No se puede eliminar una unidad didáctica activa.");
        }

        if ($course->studyPlanDetails()->exists()) {
            throw new Exception("No se puede eliminar una unidad didáctica asociada a un plan de estudios.");
        }

        $course->delete();

        return "Unidad Didáctica eliminada correctamente.";
    }
}
