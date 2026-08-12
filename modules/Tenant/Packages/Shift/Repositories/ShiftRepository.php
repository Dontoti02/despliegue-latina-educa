<?php

namespace Modules\Tenant\Packages\Shift\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Shift\Helpers\ShiftHelper;
use Modules\Tenant\Models\Shift;

class ShiftRepository
{
    public static function list(Request $request)
    {
        ShiftHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $shifts = Shift::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $shifts->total(),
            'items' => $shifts->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        ShiftHelper::validateRequest($request);

        $name = $request->input('name');

        $existsShiftName = Shift::select()
            ->where('name', $name)
            ->exists();

        if ($existsShiftName) {
            throw new Exception('El nombre del turno ya existe.');
        }

        Shift::create([
            'name' => $name,
        ]);

        return 'Turno creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        ShiftHelper::validateRequest($request);

        $name = $request->input('name');

        $shift = Shift::findOrFail($id);

        $existsShiftName = Shift::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsShiftName) {
            throw new Exception('El nombre del turno ya existe.');
        }

        $shift->update([
            'name' => $name,
        ]);

        return 'Turno actualizado correctamente.';
    }

    public static function delete(int $id)
    {
        $shift = Shift::findOrFail($id);

        if ($shift->classrooms()->exists()) {
            throw new Exception('No se puede eliminar el turno porque tiene clases asociadas.');
        }

        if ($shift->enrollments()->exists()) {
            throw new Exception('No se puede eliminar el turno porque tiene matriculas asociadas.');
        }

        $shift->delete();

        return "Turno eliminado correctamente.";
    }
}
