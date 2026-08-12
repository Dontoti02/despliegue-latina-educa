<?php

namespace Modules\Tenant\Packages\StudyPlanType\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyPlanType\Helpers\StudyPlanTypeHelper;
use Modules\Tenant\Models\StudyPlanType;

class StudyPlanTypeRepository
{
    public static function list(Request $request)
    {
        StudyPlanTypeHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $studyPlanTypes = StudyPlanType::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $studyPlanTypes->total(),
            'items' => $studyPlanTypes->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        StudyPlanTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $existsStudyPlanTypeName = StudyPlanType::select()
            ->where('name', $name)
            ->exists();

        if ($existsStudyPlanTypeName) {
            throw new Exception('El nombre del tipo de plan de estudio ya existe.');
        }

        StudyPlanType::create([
            'name' => $name,
        ]);

        return 'Tipo de plan de estudio creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        StudyPlanTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $studyPlanType = StudyPlanType::findOrFail($id);

        $existsStudyPlanTypeName = StudyPlanType::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsStudyPlanTypeName) {
            throw new Exception('El nombre del tipo de plan de estudio ya existe.');
        }

        $studyPlanType->update([
            'name' => $name,
        ]);

        return 'Tipo de plan de estudio actualizado correctamente.';
    }

    public static function delete(int $id)
    {
        $studyPlanType = StudyPlanType::findOrFail($id);

        if ($studyPlanType->studyPlans()->exists()) {
            throw new Exception('No se puede eliminar el tipo de plan de estudio porque tiene planes de estudios asociados.');
        }

        $studyPlanType->delete();

        return "Tipo de plan de estudio eliminado correctamente.";
    }
}
