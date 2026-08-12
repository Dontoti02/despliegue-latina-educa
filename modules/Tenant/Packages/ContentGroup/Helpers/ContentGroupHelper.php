<?php

namespace Modules\Tenant\Packages\ContentGroup\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\ContentGroup;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;

class ContentGroupHelper
{
    public static function validateRequest(Request $request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "$required|numeric|exists:classroom,id",
            "title"         => "required|string|max:255|unique:content_group,title,$exceptId,id,classroom_id,$request->classroom_id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $content_group = ContentGroup::find($request->model_id);

        $classroom = $content_group->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'academic_secretary,administrador,teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $result = (object) [
            'polymorphic' => $content_group,
            'classroom_id' => $classroom->id,
        ];

        return $result;
    }

    public static function validateDelete(string $model, Model $polymorphic)
    {
        $content_group = $model === 'file'
            ? $polymorphic->fileable
            : $polymorphic->linkable;

        $classroom = $content_group->classroom;

        ClassroomHelper::validateAccess($classroom->id, 'academic_secretary,administrador,teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }
    }
}
