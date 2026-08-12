<?php

namespace Modules\Tenant\Packages\Training\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Training\Enums\TrainingAnswerStatusEnum;
use Modules\Tenant\Models\TrainingAnswer;
use Modules\Tenant\Models\User;

class TrainingAnswerHelper
{
    public static function validateUploadFile(Request $request)
    {
        $user = User::authenticated();

        $answer = TrainingAnswer::find($request->model_id);

        if ($answer->person_id !== $user->person_id) {
            throw new Exception("La respuesta no te pertenece");
        }

        if (in_array($answer->status, [TrainingAnswerStatusEnum::DELIVERED, TrainingAnswerStatusEnum::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        $content = $answer->content;
        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validatePeriod($training_id);

        if (!$content->is_visible) {
            throw new Exception('El contenido aun no es visible');
        }

        if ($answer->status === TrainingAnswerStatusEnum::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $result = (object) [
            'polymorphic' => $answer,
            'training_id' => $training_id,
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

        if (in_array($answer->status, [TrainingAnswerStatusEnum::DELIVERED, TrainingAnswerStatusEnum::OVERDUE])) {
            throw new Exception('La respuesta ya ha sido entregada');
        }

        if ($answer->status === TrainingAnswerStatusEnum::EVALUATED) {
            throw new Exception('La tarea ya ha sido evaluada');
        }

        $content = $answer->content;
        $training_id = $content->contentGroup->training_id;

        TrainingHelper::validatePeriod($training_id);
    }
}
