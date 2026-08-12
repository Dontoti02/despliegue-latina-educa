<?php

namespace Modules\Tenant\Packages\Course\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "search"            => "nullable|string",
            "study_program_id"  => "nullable|integer|exists:study_program,id",
            "module_id"         => "nullable|integer|exists:module,id",
            "type_id"           => "nullable|integer|exists:course_type,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "study_program_id"  => "required|integer|exists:study_program,id",
            "module_id"         => "nullable|integer|exists:module,id",
            "type_id"           => "required|integer|exists:course_type,id",
            "code"              => "required|string",
            "name"              => "required|string",
            "year"              => "required|string|digits:4",
            "credits"           => "required|numeric|min:1",
            "hours"             => "required|integer|min:1",
            "description"       => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
