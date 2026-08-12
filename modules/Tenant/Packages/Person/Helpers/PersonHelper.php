<?php

namespace Modules\Tenant\Packages\Person\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PersonHelper
{
    public static function validateSearchRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type'          => 'required|string|in:person,teacher,student',
            'value'         => 'nullable|string|max:255',
            'to_create'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
