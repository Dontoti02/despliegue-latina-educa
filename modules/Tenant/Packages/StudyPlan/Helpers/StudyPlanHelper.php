<?php

namespace Modules\Tenant\Packages\StudyPlan\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudyPlanHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"              => "required|integer|gt:0",
            "size"              => "required|integer|gt:0",
            "search"            => "nullable|string",
            "study_program_id"  => "nullable|integer|exists:study_program,id",
            "type_id"           => "nullable|integer|exists:study_plan_type,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "study_program_id"          => "required|integer|exists:study_program,id",
            "type_id"                   => "required|integer|exists:study_plan_type,id",
            "name"                      => "required|string",
            "year"                      => "required|string|digits:4",
            "score_min_to_pass_number"  => "required|numeric|gt:0",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
