<?php

namespace Modules\Tenant\Packages\Cycle\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Packages\Cycle\Helpers\CycleHelper;

class CycleRepository
{
    public static function list(Request $request)
    {
        CycleHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $cycles = Cycle::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('order', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $cycles->total(),
            'items' => $cycles->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        CycleHelper::validateRequest($request);

        $name = $request->input('name');

        $existsCycleName = Cycle::select()
            ->where('name', $name)
            ->exists();

        if ($existsCycleName) {
            throw new Exception("Ya existe un ciclo con el mismo nombre.");
        }

        $lastOrder = Cycle::max('order');

        Cycle::create([
            'name' => $name,
            'order' => $lastOrder ? $lastOrder + 1 : 1,
        ]);

        return 'Periodo Académico creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        CycleHelper::validateRequest($request);

        $name = $request->input('name');

        $cycle = Cycle::findOrFail($id);

        $existsCycleName = Cycle::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsCycleName) {
            throw new Exception("Ya existe un ciclo con el mismo nombre.");
        }

        if ($cycle->studyPlanDetails()->exists()) {
            throw new Exception("No se puede actualizar un ciclo asociado a un plan de estudios.");
        }

        $cycle->update([
            'name' => $name,
        ]);

        return 'Periodo Académico actualizado correctamente.';
    }

    public static function sort(int $id, int $position)
    {
        $cycle = Cycle::findOrFail($id);

        if ($position !== 1 && $position !== -1) {
            throw new Exception('La posición debe ser 1 o -1.');
        }

        $currentOrder = $cycle->order;

        $newOrder = $currentOrder - $position;

        $maxOrder = Cycle::max('order');

        if ($newOrder < 1) {
            $newOrder = 1;
        }

        if ($newOrder > $maxOrder) {
            $newOrder = $maxOrder;
        }

        $cycles = Cycle::select()
            ->orderBy('order', 'asc')
            ->get();

        foreach ($cycles as $item) {
            $order = $item->order;

            if ($order >= $newOrder && $order < $currentOrder) {
                $order++;
            }

            if ($order <= $newOrder && $order > $currentOrder) {
                $order--;
            }

            if ($item->id === $id) {
                $order = $newOrder;
            }

            $item->update([
                'order' => $order,
            ]);
        }

        return 'Periodo Académico reordenado correctamente.';
    }

    public static function delete(int $id)
    {
        $cycle = Cycle::findOrFail($id);

        if ($cycle->studyPlanDetails()->exists()) {
            throw new Exception("No se puede eliminar un ciclo que tiene planes de estudios asociados.");
        }

        $cycle->delete();

        return "Periodo Académico eliminado correctamente.";
    }
}
