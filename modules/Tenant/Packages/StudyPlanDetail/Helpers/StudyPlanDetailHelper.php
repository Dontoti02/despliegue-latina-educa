<?php

namespace Modules\Tenant\Packages\StudyPlanDetail\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudyPlanDetailHelper
{
    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'study_plan_id'     => 'required|integer|exists:study_plan,id',
            'cycle_id'          => 'required|integer|exists:cycle,id',
            'course_id'         => 'required|integer|exists:course,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
