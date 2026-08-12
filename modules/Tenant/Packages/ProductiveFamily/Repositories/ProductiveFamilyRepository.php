<?php

namespace Modules\Tenant\Packages\ProductiveFamily\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\ProductiveFamily\Helpers\ProductiveFamilyHelper;
use Modules\Tenant\Models\ProductiveFamily;

class ProductiveFamilyRepository
{
    public static function list(Request $request)
    {
        ProductiveFamilyHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $productiveFamilies = ProductiveFamily::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $productiveFamilies->total(),
            'items' => $productiveFamilies->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        ProductiveFamilyHelper::validateRequest($request);

        $name = $request->input('name');

        $existsProductiveFamilyName = ProductiveFamily::select()
            ->where('name', $name)
            ->exists();

        if ($existsProductiveFamilyName) {
            throw new Exception('El nombre de la familia productiva ya existe.');
        }

        ProductiveFamily::create([
            'name' => $name,
        ]);

        return 'Familia productiva creada correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        ProductiveFamilyHelper::validateRequest($request);

        $name = $request->input('name');

        $productiveFamily = ProductiveFamily::findOrFail($id);

        $existsProductiveFamilyName = ProductiveFamily::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsProductiveFamilyName) {
            throw new Exception('El nombre de la familia productiva ya existe.');
        }

        $productiveFamily->update([
            'name' => $name,
        ]);

        return 'Familia productiva actualizada correctamente.';
    }

    public static function delete(int $id)
    {
        $productiveFamily = ProductiveFamily::findOrFail($id);

        if ($productiveFamily->studyPrograms()->exists()) {
            throw new Exception('No se puede eliminar la familia productiva porque tiene programas de estudios asociados.');
        }

        $productiveFamily->delete();

        return "Familia productiva eliminada correctamente.";
    }
}
