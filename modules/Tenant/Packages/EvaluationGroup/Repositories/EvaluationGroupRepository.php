<?php

namespace Modules\Tenant\Packages\EvaluationGroup\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\EvaluationGroup\Helpers\EvaluationGroupHelper;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\EvaluationGroup;

class EvaluationGroupRepository
{
    public static function list(int $classroomId)
    {
        Classroom::findOrFail($classroomId);

        $evaluationGroups = EvaluationGroup::select('id', 'title', 'weight')
            ->where('classroom_id', $classroomId)
            ->get();

        return $evaluationGroups;
    }

    public static function set(Request $request)
    {
        EvaluationGroupHelper::validateSetRequest($request);

        $classroomId = $request->input('classroom_id');
        $createItems = $request->input('create', []);
        $updateItems = $request->input('update', []);
        $deleteIds = $request->input('delete', []);

        $classroom = Classroom::findOrFail($classroomId);

        ClassroomHelper::validateAccess($classroomId, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $evaluationGroups = EvaluationGroup::select()
            ->where('classroom_id', $classroomId)
            ->whereNotIn('id', array_column($updateItems, 'id'))
            ->whereNotIn('id', $deleteIds)
            ->get();

        $totalWeight = $evaluationGroups->sum('weight');

        $totalWeight += array_sum(array_column($createItems, 'weight'));
        $totalWeight += array_sum(array_column($updateItems, 'weight'));

        if ($totalWeight != 1) {
            throw new Exception('La suma de los pesos debe ser igual a 1.');
        }

        $createAndUpdateItems = array_merge($createItems, $updateItems);

        if (count($deleteIds) > 0) {
            $evaluationGroupsToDelete = EvaluationGroup::select()
                ->whereIn('id', $deleteIds)
                ->get();

            foreach ($evaluationGroupsToDelete as $evaluationGroup) {
                if ($evaluationGroup->contents()->exists()) {
                    throw new Exception("No se puede eliminar el grupo de evaluación ($evaluationGroup->title) porque tiene evaluaciones asociadas.");
                }

                $evaluationGroup->delete();
            }
        }

        foreach ($createAndUpdateItems as $item) {
            $id = $item['id'] ?? null;
            $title = $item['title'];
            $weight = $item['weight'];

            $existsTitle = EvaluationGroup::select()
                ->when($id, function ($query) use ($id) {
                    $query->where('id', '!=', $id);
                })
                ->where('classroom_id', $classroomId)
                ->where('title', $title)
                ->exists();

            if ($existsTitle) {
                throw new Exception("Ya existe un grupo de evaluación con el mismo nombre ($title)");
            }

            if ($id) {
                EvaluationGroup::findOrFail($id)
                    ->update([
                        'title' => $title,
                        'weight' => $weight
                    ]);
            } else {
                EvaluationGroup::create([
                    'classroom_id' => $classroomId,
                    'title' => $title,
                    'weight' => $weight,
                ]);
            }
        }

        $result = self::list($classroomId);

        return $result;
    }
}
