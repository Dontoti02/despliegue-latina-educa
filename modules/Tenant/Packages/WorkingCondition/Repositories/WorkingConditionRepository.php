<?php

namespace Modules\Tenant\Packages\WorkingCondition\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\WorkingCondition\Helpers\WorkingConditionHelper;
use Modules\Tenant\Models\WorkingCondition;

class WorkingConditionRepository
{
    public static function list(Request $request)
    {
        WorkingConditionHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $workingConditions = WorkingCondition::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $workingConditions->total(),
            'items' => $workingConditions->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        WorkingConditionHelper::validateRequest($request);

        $name = $request->input('name');

        $existsWorkingConditionName = WorkingCondition::select()
            ->where('name', $name)
            ->exists();

        if ($existsWorkingConditionName) {
            throw new Exception('El nombre de la condición laboral ya existe.');
        }

        WorkingCondition::create([
            'name' => $name,
        ]);

        return 'Condición laboral creada correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        WorkingConditionHelper::validateRequest($request);

        $name = $request->input('name');

        $workingCondition = WorkingCondition::findOrFail($id);

        $existsWorkingConditionName = WorkingCondition::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsWorkingConditionName) {
            throw new Exception('El nombre de la condición laboral ya existe.');
        }

        $workingCondition->update([
            'name' => $name,
        ]);

        return 'Condición laboral actualizada correctamente.';
    }

    public static function delete(int $id)
    {
        $workingCondition = WorkingCondition::findOrFail($id);

        if ($workingCondition->teachers()->exists()) {
            throw new Exception('No se puede eliminar la condición laboral porque tiene docentes asociados.');
        }

        $workingCondition->delete();

        return "Condición laboral eliminada correctamente.";
    }
}
