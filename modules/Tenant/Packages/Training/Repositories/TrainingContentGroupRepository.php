<?php

namespace Modules\Tenant\Packages\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Training\Helpers\TrainingContentGroupHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingHelper;
use Modules\Tenant\Models\Training;
use Modules\Tenant\Models\TrainingContentGroup;
use Modules\Tenant\Models\User;

class TrainingContentGroupRepository
{
    public static function list(int $training_id)
    {
        $training = Training::findOrFail($training_id);

        $content_groups = $training->contentGroups()
            ->select('id', 'title')
            ->whereNot('title', 'Sílabo')
            ->get();

        return $content_groups;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        TrainingContentGroupHelper::validateRequest($request);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);
        TrainingHelper::validatePeriod($request->training_id);

        $content_group = TrainingContentGroup::create([
            'training_id' => $request->training_id,
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $content_group = TrainingContentGroup::findOrFail($id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede modificar el grupo de contenido Sílabo');
        }

        $request->merge(['training_id' => $content_group->training_id]);

        TrainingContentGroupHelper::validateRequest($request, $id);
        TrainingHelper::validateTeacherAccess($user->person_id, $request->training_id);
        TrainingHelper::validatePeriod($request->training_id);

        $content_group->update([
            'title' => $request->title,
        ]);

        return $content_group;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $content_group = TrainingContentGroup::findOrFail($id);

        if ($content_group->title == 'Sílabo') {
            throw new Exception('No se puede eliminar el grupo de contenido Sílabo');
        }

        TrainingHelper::validateTeacherAccess($user->person_id, $content_group->training_id);
        TrainingHelper::validatePeriod($content_group->training_id);

        if ($content_group->contents()->exists()) {
            throw new Exception('No se puede eliminar el grupo de contenido porque tiene contenido asociado');
        }

        $content_group->delete();

        return 'Grupo de contenido eliminado correctamente';
    }
}
