<?php

namespace Modules\Tenant\Packages\Publication\Helpers;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Publication;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;

class PublicationHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"          => "required|integer|gt:0",
            "size"          => "required|integer|gt:0",
            "classroom_id"  => "required|numeric|exists:classroom,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest($request, $exceptId = 0)
    {
        $required = $exceptId ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"      => "$required|numeric|exists:classroom,id",
            "value"             => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUploadFile(Request $request)
    {
        $user = User::authenticated();

        $publication = Publication::find($request->model_id);

        if ($publication->person_id !== $user->person_id) {
            throw new Exception("La publicación no te pertenece");
        }

        $classroom = $publication->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $result = (object) [
            'polymorphic' => $publication,
            'classroom_id' => $classroom->id,
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

        $classroom = $publication->classroom;

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }
    }
}
