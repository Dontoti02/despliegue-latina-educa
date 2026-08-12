<?php

namespace Modules\Tenant\Packages\Module\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuleHelper
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
            "study_program_id"  => "required|integer|exists:study_program,id",
            "type_id"           => "required|integer|exists:module_type,id",
            "name"              => "required|string",
            "year"              => "required|string|digits:4",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
