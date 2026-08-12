<?php

namespace Modules\Tenant\Packages\Answer\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Answer;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Answer\Enums\AnswerStatus;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;

class AnswerHelper
{
    public static function validateUploadFile(Request $request)
    {
        $user = User::authenticated();
        $answer = Answer::find($request->model_id);

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if (in_array($answer->status, [AnswerStatus::DELIVERED, AnswerStatus::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        $content = $answer->content;
        $classroom = $content->contentGroup->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        if (!$content->is_visible) {
            throw new Exception('El contenido aun no es visible');
        }

        if ($answer->status === AnswerStatus::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $result = (object) [
            'polymorphic' => $answer,
            'classroom_id' => $classroom->id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $user = User::authenticated();

        $answer = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if (in_array($answer->status, [AnswerStatus::DELIVERED, AnswerStatus::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        if ($answer->status === AnswerStatus::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $content = $answer->content;
        $classroom = $content->contentGroup->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }
    }
}
