<?php

namespace Modules\Tenant\Packages\Training\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\TrainingContentGroup;
use Modules\Tenant\Models\User;

class TrainingContentGroupHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "training_id"   => "$required|numeric|exists:training,id",
            "title"         => "required|string|max:255|unique:training_content_group,title,$exceptId,id,training_id,$request->training_id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $user = User::authenticated();
        $training_content_group = TrainingContentGroup::find($request->model_id);

        $training_id = $training_content_group->training_id;

        TrainingHelper::validateASTAccess($user->person_id, $user->rol_id, $training_id);
        TrainingHelper::validatePeriod($training_id);

        $result = (object) [
            'polymorphic' => $training_content_group,
            'training_id' => $training_id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $user = User::authenticated();
        $training_content_group = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $training_id = $training_content_group->training_id;

        TrainingHelper::validateASTAccess($user->person_id, $user->rol_id, $training_id);
        TrainingHelper::validatePeriod($training_id);
    }
}
