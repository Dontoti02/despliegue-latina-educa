<?php

namespace Modules\Tenant\Packages\EvaluationGroup\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationGroupHelper
{
    public static function validateSetRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "classroom_id"      => "required|integer|exists:classroom,id",

            "create"            => "nullable|array",
            "create.*.title"    => "required|string|max:255",
            "create.*.weight"   => "required|numeric|between:0.01,1",

            "update"            => "nullable|array",
            "update.*.id"       => "required|integer|exists:evaluation_group,id",
            "update.*.title"    => "required|string|max:255",
            "update.*.weight"   => "required|numeric|between:0.01,1",

            "delete"            => "nullable|array",
            "delete.*"          => "required|integer|exists:evaluation_group,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
