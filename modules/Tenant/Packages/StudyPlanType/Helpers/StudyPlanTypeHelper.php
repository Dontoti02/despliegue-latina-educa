<?php

namespace Modules\Tenant\Packages\StudyPlanType\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudyPlanTypeHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "search"            => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
