<?php

namespace Modules\Tenant\Packages\ContentGroup\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\ContentGroup\Helpers\ContentGroupHelper;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\ContentGroup;

class ContentGroupRepository
{
    public static function list(int $classroom_id)
    {
        $classroom = Classroom::findOrFail($classroom_id);

        $content_groups = $classroom->contentGroups()
            ->select('id', 'title')
            ->whereNot('title', 'Sílabo')
            ->get();

        return $content_groups;
    }

    public static function create(Request $request)
    {
        ContentGroupHelper::validateRequest($request);

        $classroomId = $request->input('classroom_id');

        $classroom = Classroom::findOrFail($classroomId);

        ClassroomHelper::validateAccess($classroomId, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $content_group = ContentGroup::create([
            'classroom_id' => $request->classroom_id,
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function update(int $id, Request $request)
    {
        $content_group = ContentGroup::findOrFail($id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede modificar el grupo de contenido Sílabo');
        }

        $request->merge(['classroom_id' => $content_group->classroom_id]);

        ContentGroupHelper::validateRequest($request, $id);

        $classroomId = $request->input('classroom_id');

        $classroom = Classroom::findOrFail($classroomId);

        ClassroomHelper::validateAccess($classroomId, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $content_group->update([
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function delete(int $id)
    {
        $content_group = ContentGroup::findOrFail($id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede eliminar el grupo de contenido Sílabo');
        }

        $classroom = $content_group->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if ($content_group->contents()->exists()) {
            throw new Exception('No se puede eliminar el grupo de contenido porque tiene contenido asociado');
        }

        $content_group->delete();

        return 'Grupo de contenido eliminado correctamente';
    }
}
