<?php

namespace Modules\Tenant\Packages\Training\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\TrainingPublication;
use Modules\Tenant\Models\User;

class TrainingPublicationHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|integer|gt:0",
            "size"          => "required|integer|gt:0",
            "training_id"   => "required|numeric|exists:training,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "training_id"       => "$required|numeric|exists:training,id",
            "value"             => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $user = User::authenticated();
        $publication = TrainingPublication::find($request->model_id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $training_id = $publication->training_id;

        TrainingHelper::validatePeriod($training_id);

        $result = (object) [
            'polymorphic' => $publication,
            'training_id' => $training_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $user = User::authenticated();
        $publication = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $training_id = $publication->training_id;

        TrainingHelper::validatePeriod($training_id);
    }
}
