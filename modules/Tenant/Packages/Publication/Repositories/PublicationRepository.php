<?php

namespace Modules\Tenant\Packages\Publication\Repositories;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Publication;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\Comment\Repositories\CommentRepository;
use Modules\Tenant\Packages\Publication\Helpers\PublicationHelper;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Services\FileTenantService;

class PublicationRepository
{
    public static function list(Request $request)
    {
        PublicationHelper::validateListRequest($request);

        $publications = Publication::select([
            'publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'publication.created_at as date',
            'publication.value',
        ])
            ->join('person', 'publication.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('publication.classroom_id', $request->classroom_id)
            ->orderBy('publication.created_at', 'desc')
            ->paginate($request->size, ['*'], 'page', $request->page);

        $aux = [];
        foreach ($publications->items() as $publication) {

            $aux[] = [
                ...$publication->toArray(),
                'files' => FileRepository::listByModel($publication),
                'comments' => CommentRepository::listByModel($publication),
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
        $publication = Publication::select([
            'publication.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'publication.created_at as date',
            'publication.value',
        ])
            ->join('person', 'publication.person_id', '=', 'person.id')
            ->join('user', 'person.id', '=', 'user.person_id')
            ->where('publication.id', $id)
            ->first();

        $result = [
            ...$publication->toArray(),
            'files' => FileRepository::listByModel($publication),
            'comments' => CommentRepository::listByModel($publication),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        PublicationHelper::validateRequest($request);

        $classroomId = $request->input('classroom_id');

        $classroom = Classroom::findOrFail($classroomId);

        ClassroomHelper::validateAccess($classroomId);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $publication = Publication::create([
            'classroom_id' => $classroomId,
            'person_id' => $user->person_id,
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $publication = Publication::findOrFail($id);

        PublicationHelper::validateRequest($request, $id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para editar esta publicación");
        }

        $classroom = $publication->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $publication->update([
            'value' => $request->value,
        ]);

        $result = self::get($publication->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $publication = Publication::findOrFail($id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("No tienes permisos para eliminar esta publicación");
        }

        $classroom = $publication->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

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
