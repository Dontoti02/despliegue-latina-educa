<?php

namespace Modules\Tenant\Packages\Comment\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\Comment\Helpers\CommentHelper;
use Modules\Tenant\Models\Comment;
use Modules\Tenant\Models\Content;
use Modules\Tenant\Models\Publication;
use Modules\Tenant\Models\User;

class CommentRepository
{
    public static function listByModel($model)
    {
        $comments = $model->comments()
            ->select([
                'comment.id',
                'user.id as user_id',
                'person.names as person',
                'user.avatar as photo',
                'comment.created_at as date',
                'comment.value',
            ])
            ->join('person', 'comment.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->orderBy('comment.created_at', 'desc')
            ->get();

        return $comments;
    }

    public static function get(int $id)
    {
        $comment = Comment::select([
            'comment.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'comment.created_at as date',
            'comment.value',
        ])
            ->join('person', 'comment.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('comment.id', $id)
            ->first();

        return $comment;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        CommentHelper::validateRequest($request);

        $models = [
            "content" => Content::class,
            "publication" => Publication::class,
        ];

        $morph = $models[$request->model]::find($request->model_id);

        $classroom = $morph->contentGroup?->classroom ?? $morph->classroom;

        ClassroomHelper::validateAccess($classroom->id);

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $comment = $morph->comments()->create([
            'person_id' => $user->person_id,
            'value' => $request->comment,
        ]);

        $result = self::get($comment->id);

        return $result;
    }

    public static function update(int $id, Request $request)
    {
        $user = User::authenticated();

        $comment = Comment::findOrFail($id);

        CommentHelper::validateRequest($request, $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para editar este comentario');
        }

        $morph = $comment->commentable;
        $classroom = $morph->contentGroup?->classroom ?? $morph->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $comment->update([
            'value' => $request->comment,
        ]);

        $result = self::get($comment->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $comment = Comment::findOrFail($id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para eliminar este comentario');
        }

        $morph = $comment->commentable;
        $classroom = $morph->contentGroup?->classroom ?? $morph->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $comment->delete();

        return 'Comentario eliminado correctamente';
    }
}
