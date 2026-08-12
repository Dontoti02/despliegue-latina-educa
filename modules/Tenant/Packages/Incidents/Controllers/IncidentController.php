<?php

namespace Modules\Tenant\Packages\Incidents\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Incidents\UseCases\IncidentUseCases;

class IncidentController extends Controller
{
    public function check(string $incident_number)
    {
        return IncidentUseCases::check($incident_number);
    }

    public function getParams()
    {
        return IncidentUseCases::getParams();
    }

    public function create(Request $request)
    {
        return IncidentUseCases::create($request);
    }

    public function resolveObservation(Request $request)
    {
        return IncidentUseCases::resolveObservation($request);
    }

    public function downloadFile(Request $request)
    {
        $file_name = $request->input('path');
        return IncidentUseCases::downloadFile($file_name);
    }

    public function list(Request $request)
    {
        return IncidentUseCases::list($request);
    }

    public function markAsReviewed(int $incident_id)
    {
        return IncidentUseCases::markAsReviewed($incident_id);
    }

    public function newObservation(int $incident_id, Request $request)
    {
        return IncidentUseCases::newObservation($incident_id,$request);
    }

    public function closeIncident(Request $request)
    {
        return IncidentUseCases::closeIncident($request);
    }

    public function getIncidentTypes(Request $request)
    {
        return IncidentUseCases::getIncidentTypes($request);
    }

    public function createIncidentType(Request $request)
    {
        return IncidentUseCases::createIncidentType($request);
    }

    public function updateIncidentType(int $incident_type_id, Request $request)
    {
        return IncidentUseCases::updateIncidentType($incident_type_id, $request);
    }

    public function deleteIncidentType(int $incident_type_id)
    {
        return IncidentUseCases::deleteIncidentType($incident_type_id);
    }

    public function dashboard(Request $request)
    {
        return IncidentUseCases::dashboard($request);
    }

    public function getUserIncidents()
    {
        return IncidentUseCases::getUserIncidents();
    }
}
