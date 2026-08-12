<?php

namespace Modules\Tenant\Packages\Incidents\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Incidents\Repositories\IncidentRepository;

class IncidentUseCases
{
    public static function check(string $incident_number)
    {
        try {
            $result = IncidentRepository::check($incident_number);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function resolveObservation(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::resolveObservation($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function getParams()
    {
        try {
            $result = IncidentRepository::getParams();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::create($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function downloadFile(string $file_name)
    {
        try {
            $result = IncidentRepository::downloadFile($file_name);
            return $result;
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = IncidentRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function markAsReviewed(int $incident_id)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::markAsReviewed($incident_id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function newObservation(int $incident_id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::newObservation($incident_id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function closeIncident(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::closeIncident($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function getIncidentTypes(Request $request)
    {
        try {
            $result = IncidentRepository::getIncidentTypes($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function createIncidentType(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::createIncidentType($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function updateIncidentType(int $incident_type_id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::updateIncidentType($incident_type_id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function deleteIncidentType(int $incident_type_id)
    {
        DB::beginTransaction();
        try {
            $result = IncidentRepository::deleteIncidentType($incident_type_id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function dashboard(Request $request)
    {
        try {
            $result = IncidentRepository::dashboard($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function getUserIncidents()
    {
        try {
            $result = IncidentRepository::getUserIncidents();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
