<?php

namespace Modules\Tenant\Packages\AcademicHistory\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademicHistoryHelper
{
    public static function validateListRequest(Request $request, bool $isStudent)
    {
        $required = $isStudent ? 'nullable' : 'required';

        $validator = Validator::make($request->all(), [
            "student_id"        => $required . "|numeric|exists:student,id",
            "study_plan_id"     => "required|numeric|exists:study_plan,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
