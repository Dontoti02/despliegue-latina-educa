<?php

namespace Modules\Tenant\Packages\SyllabusManager\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SyllabusProgressHelper
{
    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'page'              => 'required|integer|gt:0',
            'size'              => 'required|integer|gt:0',
            'search'            => 'nullable|string',
            'period_id'         => 'nullable|integer|exists:period,id',
            'study_program_id'  => 'nullable|integer|exists:study_program,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateReportRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search'            => 'nullable|string',
            'period_id'         => 'nullable|integer|exists:period,id',
            'study_program_id'  => 'nullable|integer|exists:study_program,id',
            'course_ids'        => 'nullable|array',
            'course_ids.*'      => 'integer|exists:course,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
