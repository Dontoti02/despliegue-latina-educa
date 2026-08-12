<?php

namespace Modules\Tenant\Packages\Section\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Section\Helpers\SectionHelper;
use Modules\Tenant\Models\Section;

class SectionRepository
{
    public static function list(Request $request)
    {
        SectionHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $sections = Section::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $sections->total(),
            'items' => $sections->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        SectionHelper::validateRequest($request);

        $name = $request->input('name');

        $existsSectionName = Section::select()
            ->where('name', $name)
            ->exists();

        if ($existsSectionName) {
            throw new Exception('El nombre de la sección ya existe.');
        }

        Section::create([
            'name' => $name,
        ]);

        return 'Sección creada correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        SectionHelper::validateRequest($request);

        $name = $request->input('name');

        $section = Section::findOrFail($id);

        $existsSectionName = Section::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsSectionName) {
            throw new Exception('El nombre de la sección ya existe.');
        }

        $section->update([
            'name' => $name,
        ]);

        return 'Sección actualizada correctamente.';
    }

    public static function delete(int $id)
    {
        $section = Section::findOrFail($id);

        if ($section->classrooms()->exists()) {
            throw new Exception('No se puede eliminar la sección porque tiene clases asociadas.');
        }

        if ($section->enrollments()->exists()) {
            throw new Exception('No se puede eliminar la sección porque tiene matriculas asociadas.');
        }

        $section->delete();

        return "Sección eliminada correctamente.";
    }
}
