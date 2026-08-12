<?php

namespace Modules\Tenant\Packages\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Packages\Training\Helpers\TrainingHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingPublicationHelper;
use Modules\Tenant\Models\Training;
use Modules\Tenant\Models\TrainingPublication;
use Modules\Tenant\Models\User;
use Modules\Tenant\Services\FileTenantService;

class TrainingPublicationRepository
{
    public static function list(Request $request)
    {
        TrainingPublicationHelper::validateListRequest($request);

        $publications = TrainingPublication::select([
            'training_publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_publication.created_at as date',
            'training_publication.value',
        ])
            ->join('person', 'training_publication.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('training_publication.training_id', $request->training_id)
            ->orderBy('training_publication.created_at', 'desc')
            ->paginate($request->size, ['*'], 'page', $request->page);

        $aux = [];
        foreach ($publications->items() as $publication) {

            $aux[] = [
                ...$publication->toArray(),
                'files' => FileRepository::listByModel($publication),
                'comments' => TrainingCommentRepository::listByModel($publication),
            ];
        }

        $result = [
            'page' => $request->page,
            'size' => $request->size,
            'total' => $publications->total(),
            'publications' => $aux,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $publication = TrainingPublication::select([
            'training_publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_publication.created_at as date',
            'training_publication.value',
        ])
            ->join('person', 'training_publication.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('training_publication.id', $id)
            ->first();

        $result = [
            ...$publication->toArray(),
            'files' => FileRepository::listByModel($publication),
            'comments' => TrainingCommentRepository::listByModel($publication),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        TrainingPublicationHelper::validateRequest($request);

        TrainingHelper::validateAccess($user->person_id, $request->training_id);
        TrainingHelper::validatePeriod($request->training_id);

        $training = Training::findOrFail($request->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $publication = TrainingPublication::create([
            'training_id' => $request->training_id,
            'person_id' => $user->person_id,
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $publication = TrainingPublication::findOrFail($id);

        TrainingPublicationHelper::validateRequest($request, $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para editar esta publicación");
        }

        TrainingHelper::validatePeriod($publication->training_id);

        $training = Training::findOrFail($publication->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $publication->update([
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $publication = TrainingPublication::findOrFail($id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para eliminar esta publicación");
        }

        TrainingHelper::validatePeriod($publication->training_id);

        $training = Training::findOrFail($publication->training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $files = $publication->files;
        FileTenantService::delete($files->all());

        DB::beginTransaction();
        try {
            $publication->delete();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return 'Publicación eliminada correctamente';
    }
}
