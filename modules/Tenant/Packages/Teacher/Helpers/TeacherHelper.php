<?php

namespace Modules\Tenant\Packages\Teacher\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherHelper
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

    public static function validateCreateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_number'           => 'required|string',
            'names'                     => 'required|string',
            'email'                     => 'required|email',
            'phone'                     => 'required|string|size:9',
            'sex'                       => 'nullable|string',
            'birth_date'                => 'nullable|date',
            'native_language'           => 'nullable|string',
            'working_condition_id'      => 'required|integer|exists:working_condition,id',
            'study_program_id'          => 'nullable|integer|exists:study_program,id',
            'registration_date'         => 'required|date',
            'resolution_number'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateUpdateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'working_condition_id'      => 'required|integer|exists:working_condition,id',
            'study_program_id'          => 'nullable|integer|exists:study_program,id',
            'registration_date'         => 'required|date',
            'resolution_number'         => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
