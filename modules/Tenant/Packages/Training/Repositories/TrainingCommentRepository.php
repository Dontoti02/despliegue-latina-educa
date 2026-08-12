<?php

namespace Modules\Tenant\Packages\Training\Repositories;

use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Training\Helpers\TrainingCommentHelper;
use Modules\Tenant\Packages\Training\Helpers\TrainingHelper;
use Modules\Tenant\Models\Training;
use Modules\Tenant\Models\TrainingComment;
use Modules\Tenant\Models\TrainingContent;
use Modules\Tenant\Models\TrainingPublication;
use Modules\Tenant\Models\User;

class TrainingCommentRepository
{
    public static function listByModel($model)
    {
        $comments = $model->comments()
            ->select([
                'training_comment.id',
                'user.id as user_id',
                'person.names as person',
                'user.avatar as photo',
                'training_comment.created_at as date',
                'training_comment.value',
            ])
            ->join('person', 'training_comment.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->orderBy('training_comment.created_at', 'desc')
            ->get();

        return $comments;
    }

    public static function get(int $id)
    {
        $comment = TrainingComment::select([
            'training_comment.id',
            'user.id as user_id',
            'person.names as person',
            'user.avatar as photo',
            'training_comment.created_at as date',
            'training_comment.value',
        ])
            ->join('person', 'training_comment.person_id', '=', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->where('training_comment.id', $id)
            ->first();

        return $comment;
    }

    public static function create(Request $request)
    {
        $user = User::authenticated();

        TrainingCommentHelper::validateRequest($request);

        $models = [
            "training_content" => TrainingContent::class,
            "training_publication" => TrainingPublication::class,
        ];

        $morph = $models[$request->model]::find($request->model_id);

        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        TrainingHelper::validateAccess($user->person_id, $training_id);
        TrainingHelper::validatePeriod($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

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

        $comment = TrainingComment::findOrFail($id);

        TrainingCommentHelper::validateRequest($request, $id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para editar este comentario');
        }

        $morph = $comment->commentable;
        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        TrainingHelper::validatePeriod($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $comment->update([
            'value' => $request->comment,
        ]);

        $result = self::get($comment->id);

        return $result;
    }

    public static function delete(int $id)
    {
        $user = User::authenticated();

        $comment = TrainingComment::findOrFail($id);

        if ($comment->person_id !== $user->person_id) {
            throw new Exception('No tiene permisos para eliminar este comentario');
        }

        $morph = $comment->commentable;
        $training_id = $morph->contentGroup?->training_id ?? $morph->training_id;
        TrainingHelper::validatePeriod($training_id);

        $training = Training::findOrFail($training_id);

        TrainingHelper::checkTrainingStatus($user, $training, true);

        $comment->delete();

        return 'Comentario eliminado correctamente';
    }
}
