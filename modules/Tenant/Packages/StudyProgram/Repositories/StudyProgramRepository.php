<?php

namespace Modules\Tenant\Packages\StudyProgram\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyProgram\Helpers\StudyProgramHelper;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\ProductiveFamily;

class StudyProgramRepository
{
    public static function params()
    {
        $productiveFamilies = ProductiveFamily::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'productive_families' => $productiveFamilies,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        StudyProgramHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');
        $productiveFamilyId = $request->input('productive_family_id');

        $studyPrograms = StudyProgram::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->when($productiveFamilyId, function ($query) use ($productiveFamilyId) {
                $query->where('productive_family_id', $productiveFamilyId);
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $studyPrograms->total(),
            'items' => $studyPrograms->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        StudyProgramHelper::validateRequest($request);

        $productiveFamilyId = $request->input('productive_family_id');
        $name = $request->input('name');

        $existsStudyProgramName = StudyProgram::select()
            ->where('name', $name)
            ->exists();

        if ($existsStudyProgramName) {
            throw new Exception('El nombre del programa de estudios ya existe.');
        }

        StudyProgram::create([
            'productive_family_id' => $productiveFamilyId,
            'name' => $name,
        ]);

        return 'Programa de estudios creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        StudyProgramHelper::validateRequest($request);

        $productiveFamilyId = $request->input('productive_family_id');
        $name = $request->input('name');

        $studyProgram = StudyProgram::findOrFail($id);

        $existsStudyProgramName = StudyProgram::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsStudyProgramName) {
            throw new Exception('El nombre del programa de estudios ya existe.');
        }

        $studyProgram->update([
            'productive_family_id' => $productiveFamilyId,
            'name' => $name,
        ]);

        return 'Programa de estudios actualizado correctamente.';
    }

    public static function toggle(int $id)
    {
        $studyProgram = StudyProgram::findOrFail($id);

        $is_active = !$studyProgram->is_active;

        $studyProgram->update([
            'is_active' => $is_active,
        ]);

        $message = $is_active ? 'activado' : 'desactivado';

        return 'Programa de estudios ' . $message . ' correctamente.';
    }

    public static function delete(int $id)
    {
        $studyProgram = StudyProgram::findOrFail($id);

        if ($studyProgram->is_active) {
            throw new Exception("No se puede eliminar un programa de estudios activo.");
        }

        if ($studyProgram->studyPlans()->exists()) {
            throw new Exception('No se puede eliminar el programa de estudios porque tiene planes de estudios asociados.');
        }

        $studyProgram->delete();

        return 'Programa de estudios eliminado correctamente.';
    }
}
