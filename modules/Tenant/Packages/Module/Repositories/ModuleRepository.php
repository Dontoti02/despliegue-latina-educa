<?php

namespace Modules\Tenant\Packages\Module\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Module\Helpers\ModuleHelper;
use Modules\Tenant\Models\Module;
use Modules\Tenant\Models\ModuleType;
use Modules\Tenant\Models\StudyProgram;

class ModuleRepository
{
    public static function params()
    {
        $studyPrograms = StudyProgram::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $moduleTypes = ModuleType::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'study_programs' => $studyPrograms,
            'types' => $moduleTypes,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        ModuleHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $modules = Module::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('order', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $modules->total(),
            'items' => $modules->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        ModuleHelper::validateRequest($request);

        $studyProgramId = $request->input('study_program_id');
        $typeId = $request->input('type_id');
        $name = $request->input('name');
        $year = $request->input('year');

        $existsModuleName = Module::select()
            ->where('name', $name)
            ->exists();

        if ($existsModuleName) {
            throw new Exception('El nombre de la modalidad formativa ya existe.');
        }

        $lastOrder = Module::max('order');

        Module::create([
            'study_program_id' => $studyProgramId,
            'type_id' => $typeId,
            'name' => $name,
            'year' => $year,
            'order' => $lastOrder ? $lastOrder + 1 : 1,
        ]);

        return 'Modalidad Formativa creada correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        ModuleHelper::validateRequest($request);

        $studyProgramId = $request->input('study_program_id');
        $typeId = $request->input('type_id');
        $name = $request->input('name');
        $year = $request->input('year');

        $module = Module::findOrFail($id);

        $existsModuleName = Module::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsModuleName) {
            throw new Exception('El nombre de la modalidad formativa ya existe.');
        }

        $module->update([
            'study_program_id' => $studyProgramId,
            'type_id' => $typeId,
            'name' => $name,
            'year' => $year,
        ]);

        return 'Modalidad Formativa actualizada correctamente.';
    }

    public static function sort(int $id, int $position)
    {
        $module = Module::findOrFail($id);

        if ($position !== 1 && $position !== -1) {
            throw new Exception('La posición debe ser 1 o -1.');
        }

        $currentOrder = $module->order;

        $newOrder = $currentOrder - $position;

        $maxOrder = Module::max('order');

        if ($newOrder < 1) {
            $newOrder = 1;
        }

        if ($newOrder > $maxOrder) {
            $newOrder = $maxOrder;
        }

        $modules = Module::select()
            ->orderBy('order', 'asc')
            ->get();

        foreach ($modules as $item) {
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

        return 'Modalidad Formativa reordenada correctamente.';
    }

    public static function delete(int $id)
    {
        $module = Module::findOrFail($id);

        if ($module->courses()->exists()) {
            throw new Exception('No se puede eliminar la modalidad formativa porque tiene unidades didácticas asociadas.');
        }

        $module->delete();

        return "Modalidad Formativa eliminada correctamente.";
    }
}
