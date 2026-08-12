<?php

namespace Modules\Tenant\Packages\Incidents\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IncidentHelper
{
    public static function validateCreateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "incident_type_id"     => "required|numeric|exists:inc_incident_type,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "search"   => "nullable|string",
            "type"     => "nullable|numeric|exists:inc_incident_type,id",
            "status"   => "nullable|numeric|exists:inc_incident_status,id",
            "year"     => "nullable|numeric",
            "month"    => "nullable|numeric",
            "size"     => "nullable|numeric",
            "page"     => "nullable|numeric",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateCloseIncidentRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "status"   => "required|string|in:REJECTED,COMPLETED",
            "conclusion"     => "required|string",
            "incident_id"   => "nullable|numeric|exists:inc_incident,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateDashboardRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "year"     => "nullable|numeric",
            "month"    => "nullable|numeric",
            "typeId"   => "nullable|numeric|exists:inc_incident_type,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

}
