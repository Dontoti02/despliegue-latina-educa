<?php

namespace Modules\Tenant\Packages\StudyProgram\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudyProgramHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"                  => "required|integer|gt:0",
            "size"                  => "required|integer|gt:0",
            "search"                => "nullable|string",
            "productive_family_id"  => "nullable|integer|exists:productive_family,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "productive_family_id"      => "required|integer|exists:productive_family,id",
            "name"                      => "required|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
