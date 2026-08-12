<?php

namespace Modules\Tenant\Packages\Incidents\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Incident;
use Modules\Tenant\Models\IncidentObservation;
use Modules\Tenant\Models\IncidentStatus;
use Modules\Tenant\Models\IncidentType;
use Modules\Tenant\Packages\Incidents\Helpers\IncidentHelper;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Incidents\Enums\StatusTenant as EnumsStatusTenant;

class IncidentRepository
{

    public static function check(string $incident_number)
    {
        $incident = Incident::where('incident_number', $incident_number)
            ->select([
                'id',
                'subject',
                'description',
                'incident_type_id',
                'file_url',
                'status_id',
                'user_id',
                'incident_number',
                'register_date',
                'completion_date',
                'conclusion',
                'admin_user_id',
            ])
            ->with('type')
            ->with('status')
            ->with('user.person')
            ->with([
                'observations' => function ($q) {
                    $q->select([
                        'id',
                        'request',
                        'response',
                        'file_url',
                        'incident_id',
                        'admin_user_id',
                    ]);
                },
                'observations.adminUser' => function ($q) {
                    $q->select(['id', 'person_id']);
                },
                'observations.adminUser.person' => function ($q) {
                    $q->select(['id', 'names']);
                },
            ])
            ->first();

        if (!$incident) {
            throw new Exception("No se encontró ninguna incidencia con el número proporcionado.");
        }

        $result = $incident->toArray();
        $result['user'] = $incident->user->person->names ?? null;

        return $result;
    }

    public static function resolveObservation(Request $request)
    {
        $observationId = $request->input('observation_id');
        $response = $request->input('response');
        $file = $request->file('file');

        //guardar ruta del archivo que se envia
        $filePath = null;
        if ($file) {
            $filePath = $file->store('public/incidents/' . $observationId . '/observations');
        }
        //actualizar observación

        $observation = IncidentObservation::find($observationId);
        if (!$observation) {
            throw new Exception("No se encontró ninguna observación con el ID proporcionado.");
        }

        $observation->response = $response;
        $observation->file_url = $filePath;

        $observation->save();

        return $observation;
    }


    public static function getParams()
    {
        $incidentTypes = IncidentType::all();
        $incidentStatuses = IncidentStatus::all();
        return [
            'incident_types' => $incidentTypes,
            'incident_statuses' => $incidentStatuses,
        ];
    }
    public static function create(Request $request)
    {
        IncidentHelper::validateCreateRequest($request);
        $user = User::authenticated();

        $is_student = $user->rol_id === RolTenant::STUDENT;
        $is_teacher = $user->rol_id === RolTenant::TEACHER;

        if(!$is_student && !$is_teacher) {
            throw new Exception("El usuario no tiene permisos para crear una incidencia.");
        }

        //guardar archivo que se envia y obtener la ruta del archivo
        $filePath = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = $file->store('public/incidents');
        }

        $subject = $request->input('subject');
        $description = $request->input('description');
        $incident_type_id = $request->input('incident_type_id');
        $status_id = EnumsStatusTenant::REGISTRADO; 
        $userId = $user->id;
        $incident_number = self::generateIncidentNumber();
        $register_date = Carbon::now();
        $finish_date = null;

        //crear incidencia
        $incident = new Incident();
        $incident->subject = $subject;
        $incident->description = $description;
        $incident->file_url = $filePath;
        $incident->incident_type_id = $incident_type_id;
        $incident->status_id = $status_id;
        $incident->user_id = $userId;
        $incident->incident_number = $incident_number;
        $incident->register_date = $register_date;
        $incident->completion_date = $finish_date;
        $incident->save();

        return $incident;

    }

    private static function generateIncidentNumber()
    {
        $currentDate = Carbon::now();
        $month = $currentDate->format('m');
        $year = $currentDate->format('Y');

        // Obtener el último número de incidencia registrado en la base de datos
        $lastIncident = Incident::orderBy('id', 'desc')->first();
        $lastIncidentNumber = $lastIncident ? (int) explode('-', $lastIncident->incident_number)[0] : 0;

        // Incrementar el número de incidencia
        $newIncidentNumber = str_pad($lastIncidentNumber + 1, 4, '0', STR_PAD_LEFT);

        return "{$newIncidentNumber}-{$month}-{$year}";
    }

    public static function downloadFile(string $file_name)
    {
        $filePath = storage_path('app/' . $file_name);

        if (!file_exists($filePath)) {
            throw new Exception("El archivo solicitado no existe.");
        }

        return response()->download($filePath);
    }

    public static function list(Request $request)
    {
        IncidentHelper::validateListRequest($request);
        $user = User::authenticated();

        // El search puede contener el número de incidencia, el nombre del usuario o el asunto
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');
        $year = $request->input('year');
        $month = $request->input('month');
        $size = $request->input('size');
        $page = $request->input('page');

        $query = Incident::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('incident_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user.person', function ($q2) use ($search) {
                        $q2->where('names', 'like', "%{$search}%");
                    });
            });
        }

        if ($type) {
            $query->where('incident_type_id', $type);
        }

        if ($status) {
            $query->where('status_id', $status);
        }

        if ($year) {
            $query->whereYear('register_date', $year);
        }

        if ($month) {
            $query->whereMonth('register_date', $month);
        }

        $query->with(['type', 'status', 'user.person'])
            ->orderBy('register_date', 'desc');

        $transformIncident = function ($incident) {
            $data = $incident->toArray();
            $data['user'] = $incident->user->person->names ?? null;
            return $data;
        };

        if ($size && $page) {
            $incidents = $query->paginate($size, ['*'], 'page', $page);

            $result = [
                'page'  => (int) $page,
                'size'  => (int) $size,
                'total' => $incidents->total(),
                'items' => collect($incidents->items())->map($transformIncident),
            ];
        } else {
            $incidents = $query->get();

            $result = [
                'page'  => 1,
                'size'  => $incidents->count(),
                'total' => $incidents->count(),
                'items' => $incidents->map($transformIncident),
            ];
        }

        return $result;
    }

    public static function markAsReviewed(int $incident_id)
    {
        $incident = Incident::find($incident_id);

        if (!$incident) {
            throw new Exception("No se encontró ninguna incidencia con el ID proporcionado.");
        }

        $incident->status_id = EnumsStatusTenant::EN_REVISION;
        $incident->save();

        return $incident;
    }

    public static function newObservation(int $incident_id, Request $request)
    {
        $user = User::authenticated();

        $incident = Incident::find($incident_id);

        if (!$incident) {
            throw new Exception("No se encontró ninguna incidencia con el ID proporcionado.");
        }

        $incident->status_id = EnumsStatusTenant::OBSERVADO;
        $incident->save();

        $requestText = $request->input('request');

        //crear nueva observación
        $observation = new IncidentObservation();
        $observation->request = $requestText;
        $observation->file_url = null;
        $observation->incident_id = $incident_id;
        $observation->admin_user_id = $user->id;
        $observation->save();

        return $observation;
    }

    public static function closeIncident(Request $request)
    {
        IncidentHelper::validateCloseIncidentRequest($request);
        $incidentId = $request->input('incident_id');
        $status = $request->input('status');
        $conclusion = $request->input('conclusion');

        $incident = Incident::find($incidentId);
        if (!$incident) {
            throw new Exception("No se encontró ninguna incidencia con el ID proporcionado.");
        }
        //el status contiene el name de un status hay que traer su id
        $status_id=IncidentStatus::where('name', $status)->first()->id ?? null;
        if (!$status_id) {
            throw new Exception("No se encontró ningún estado con el nombre proporcionado.");
        }
        $incident->status_id = $status_id;
        $incident->conclusion = $conclusion;
        $incident->completion_date = Carbon::now();
        $incident->save();
        return $incident;
    }

    public static function getIncidentTypes(Request $request)
    {
        $search = $request->input('search');
        $size = $request->input('size');
        $page = $request->input('page');
        $query = IncidentType::query();

        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        $incidentTypes = $query->paginate($size, ['*'], 'page', $page);

            $result = [
                'page'  => (int) $page,
                'size'  => (int) $size,
                'total' => $incidentTypes->total(),
                'items' => collect($incidentTypes->items()),
            ];

            return $result;
    }

    public static function createIncidentType(Request $request)
    {
        $name = $request->input('name');

        $incidentType = new IncidentType();
        $incidentType->name = $name;
        $incidentType->save();

        return $incidentType;
    }

    public static function updateIncidentType(int $incident_type_id, Request $request)
    {
        $incidentType = IncidentType::find($incident_type_id);

        if (!$incidentType) {
            throw new Exception("No se encontró ningún tipo de incidencia con el ID proporcionado.");
        }

        $incidentType->name = $request->input('name');
        $incidentType->save();

        return $incidentType;
    }

    public static function deleteIncidentType(int $incident_type_id)
    {
        $incidentType = IncidentType::find($incident_type_id);

        if (!$incidentType) {
            throw new Exception("No se encontró ningún tipo de incidencia con el ID proporcionado.");
        }

        $incidentType->delete();

        return ['message' => 'Tipo de incidencia eliminado correctamente.'];
    }

    private static function generateRandomColor()
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    public static function dashboard(Request $request)
    {
        IncidentHelper::validateDashboardRequest($request);
        $year = $request->input('year');
        $month = $request->input('month');
        $type = $request->input('typeId');

         $query = Incident::query();
         if ($year) {
            $query->whereYear('register_date', $year);
         }
         if ($month) {
             $query->whereMonth('register_date', $month);
         }
         if ($type) {
             $query->where('incident_type_id', $type);
         }
         $incidents = $query->get();

         $totalIncidents = $incidents->count();
         $totalIncidentsAttended = $incidents->whereIn('status_id', [EnumsStatusTenant::RECHAZADO, EnumsStatusTenant::COMPLETADO])->count();
         $totalIncidentsNotAttended = $incidents->where('status_id', EnumsStatusTenant::REGISTRADO)->count();
         $totalIncidentsInReview = $incidents->where('status_id', EnumsStatusTenant::EN_REVISION)->count();
         $totalIncidentsObserved = $incidents->where('status_id', EnumsStatusTenant::OBSERVADO)->count();

         $last20Incidents = $incidents->sortByDesc('register_date')->take(20)->map(function ($incident) {
             return [
                 'id' => $incident->id,
                 'incident_number' => $incident->incident_number,
                 'subject' => $incident->subject,
                'status' => strtolower($incident->status->name ?? null),
                'type' => $incident->type->name ?? null,
                 'user' => $incident->user->person->names ?? null,
                 'register_date' => $incident->register_date,
             ];
         })->values();

        $incidentsByType = $incidents->groupBy('incident_type_id')->map(function ($group) use ($totalIncidents) {
            $typeName = $group->first()->type->name ?? 'Desconocido';
            $count = $group->count();
            $percentage = $totalIncidents > 0 ? ($count / $totalIncidents) * 100 : 0;
            return [
                'id' => $group->first()->incident_type_id,
                'name' => $typeName,
                'value' => $count,
                'percentage' => round($percentage, 2),
                'color' => self::generateRandomColor(),
            ];
        })->values();
        return [
            'total_incidents' => $totalIncidents,
            'total_incidents_attended' => $totalIncidentsAttended,
            'total_incidents_not_attended' => $totalIncidentsNotAttended,
            'total_incidents_in_review' => $totalIncidentsInReview,
            'total_incidents_observed' => $totalIncidentsObserved,
            'last_20_incidents' => $last20Incidents,
            'incidents_by_type' => $incidentsByType,
        ];
    }

    public static function getUserIncidents()
    {
        $user = User::authenticated();
        $incidents = Incident::where('user_id', $user->id)
            ->with(['type', 'status'])
            ->orderBy('register_date', 'desc')
            ->get();

        return $incidents->map(function ($incident) {
            return [
                'id' => $incident->id,
                'incident_number' => $incident->incident_number,
                'subject' => $incident->subject,
                'description'=> $incident->description,
                'status' => strtolower($incident->status->name ?? null),
                'type' => $incident->type->name ?? null,
                'register_date' => $incident->register_date,
            ];
        })->values();
    }
}
