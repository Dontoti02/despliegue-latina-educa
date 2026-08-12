<?php

namespace Modules\Tenant\Packages\ModuleType\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\ModuleType\Helpers\ModuleTypeHelper;
use Modules\Tenant\Models\ModuleType;

class ModuleTypeRepository
{
    public static function list(Request $request)
    {
        ModuleTypeHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $moduleTypes = ModuleType::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $moduleTypes->total(),
            'items' => $moduleTypes->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        ModuleTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $existsModuleTypeName = ModuleType::select()
            ->where('name', $name)
            ->exists();

        if ($existsModuleTypeName) {
            throw new Exception('El nombre del tipo de modalidad formativa ya existe.');
        }

        ModuleType::create([
            'name' => $name,
        ]);

        return 'Tipo de modalidad formativa creada correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        ModuleTypeHelper::validateRequest($request);

        $name = $request->input('name');

        $moduleType = ModuleType::findOrFail($id);

        $existsModuleTypeName = ModuleType::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsModuleTypeName) {
            throw new Exception('El nombre del tipo de modalidad formativa ya existe.');
        }

        $moduleType->update([
            'name' => $name,
        ]);

        return 'Tipo de modalidad formativa actualizada correctamente.';
    }

    public static function delete(int $id)
    {
        $moduleType = ModuleType::findOrFail($id);

        if ($moduleType->modules()->exists()) {
            throw new Exception('No se puede eliminar el tipo de modalidad formativa porque tiene modalidades formativas asociados.');
        }

        $moduleType->delete();

        return "Tipo de modalidad formativa eliminada correctamente.";
    }
}
