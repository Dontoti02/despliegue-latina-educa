<?php

namespace Modules\Tenant\Packages\Assistance\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Models\Assistance;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Packages\User\Enums\RolTenant;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Assistance\Helpers\AssistanceHelper;

class AssistanceRepository
{
    public static function dates(int $classroom_id)
    {
        $classroom = Classroom::findOrFail($classroom_id);

        ClassroomHelper::validateAccess($classroom_id, 'teacher');

        $dates = $classroom->assistances()
            ->selectRaw("
                DATE(assistance.created_at) as date,
                CONCAT(SUM(IF(assistance.status IN ('attended', 'late'), 1, 0)), '/', COUNT(assistance.id)) as value
            ")
            ->groupByRaw('DATE(assistance.created_at)')
            ->orderByRaw('DATE(assistance.created_at) DESC')
            ->where('assistance.deleted_at', null)
            ->get();

        return $dates;
    }

    public static function list(Request $request)
    {
        $user = User::authenticated();

        $is_teacher = $user->rol_id == RolTenant::TEACHER;

        AssistanceHelper::validateListRequest($request, $is_teacher);

        $classroom = Classroom::findOrFail($request->classroom_id);

        if ($is_teacher) {
            ClassroomHelper::validateAccess($request->classroom_id, 'teacher');

            $assistances = $classroom->assistances()
                ->select([
                    'assistance.id',
                    'person.names as person',
                    'user.avatar as photo',
                    'assistance.status',
                    'assistance.date',
                    'assistance.reason',
                ])
                ->join('person', 'assistance.person_id', 'person.id')
                ->join('user', 'person.id', 'user.person_id')
                ->whereDate('assistance.created_at', $request->date)
                ->where('assistance.deleted_at', null)
                ->orderBy('person.names', 'asc')
                ->get();

            return $assistances;
        }

        ClassroomHelper::validateAccess($request->classroom_id, 'student');

        $assistances = $classroom->assistances()
            ->selectRaw("
                assistance.id,
                DATE(assistance.created_at) as date,
                assistance.status
            ")
            ->where('assistance.person_id', $user->person_id)
            ->orderBy('assistance.created_at', 'desc')
            ->get();

        return $assistances;
    }

    public static function create(Request $request)
    {
        AssistanceHelper::validateRequest($request);

        $classroomId = $request->input('classroom_id');

        $classroom = Classroom::findOrFail($classroomId);

        ClassroomHelper::validateAccess($classroomId, 'teacher');

        if ($classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $count = Assistance::select()
            ->where('classroom_id', $classroomId)
            ->whereDate('created_at', $request->date)
            ->where('deleted_at', null)
            ->count();

        if ($count) {
            throw new Exception('Ya se ha creado la asistencia para esa fecha');
        }

        $persons_id = Participant::select()
            ->where('classroom_id', $classroomId)
            ->pluck('person_id')
            ->toArray();

        $date = Carbon::parse($request->date . ' ' . Carbon::now()->format('H:i:s'));

        $records = [];
        foreach ($persons_id as $person_id) {
            $records[] = [
                'classroom_id' => $classroomId,
                'person_id' => $person_id,
                'status' => null,
                'date' => null,
                'reason' => null,
                'created_at' => $date,
                'updated_at' => $date,
            ];
        }

        Assistance::insert($records);

        $result = self::list($request);

        return $result;
    }

    public static function mark(int $id, Request $request)
    {
        $assistance = Assistance::findOrFail($id);

        AssistanceHelper::validateMarkRequest($request);

        ClassroomHelper::validateAccess($assistance->classroom_id, 'teacher');

        if ($assistance->classroom->is_closed) {
            throw new Exception('La clase ya ha sido cerrada.');
        }

        $date = Carbon::now();

        $assistance->update([
            'status' => $request->status,
            'date' => $date,
            'reason' => $request->status == 'late' && $request->reason ? $request->reason : null,
        ]);

        return 'Asistencia actualizada correctamente';
    }

    public static function absencesPerDay(Request $request)
    {
        AssistanceHelper::validateAbsencesPerDayRequest($request);
        ClassroomHelper::validateAccess($request->classroom_id, 'teacher');

        $classroom = Classroom::findOrFail($request->classroom_id);

        $result = $classroom->assistances()
            ->selectRaw("
                assistance.id,
                person.names as person,
                IF(assistance.status = 'attended', 1, 0) as attended,
                IF(assistance.status = 'absence', 1, 0) as absence,
                IF(assistance.status = 'late', 1, 0) as late,
                assistance.reason
            ")
            ->join('person', 'assistance.person_id', 'person.id')
            ->whereDate('assistance.created_at', $request->date)
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $classroom, $request->date];
    }

    public static function consolidated(Request $request)
    {
        AssistanceHelper::validateAttendanceConsolidatedRequest($request);
        ClassroomHelper::validateAccess($request->classroom_id, 'teacher');

        $classroom = Classroom::findOrFail($request->classroom_id);

        $result = $classroom->assistances()
            ->selectRaw("
                DATE(assistance.created_at) as date,
                assistance.id,
                person.id as person_id,
                person.names as person,
                CASE
                    WHEN assistance.status = 'attended' THEN 'P'
                    WHEN assistance.status = 'absence' THEN 'F'
                    WHEN assistance.status = 'late' THEN 'T'
                    ELSE ''
                END as assistance
            ")
            ->join('person', 'assistance.person_id', 'person.id')
            ->orderBy('date', 'asc')
            ->orderBy('person.names', 'asc')
            ->get();

        $participants = $classroom->participants()
            ->selectRaw("
                person.id as id,
                person.names as person
            ")
            ->join('person', 'participant.person_id', 'person.id')
            ->orderBy('person.names', 'asc')
            ->get();

        return [$result, $classroom, $participants];
    }

    public static function deleteMultiple(Request $request)
    {
        AssistanceHelper::validateDeleteMultipleRequest($request);
        ClassroomHelper::validateAccess($request->classroom_id, 'teacher');

        $classroom = Classroom::findOrFail($request->classroom_id);

        $updatedAssistances = $classroom->assistances()
            ->select([
                'assistance.id',
                'person.names as person',
                'user.avatar as photo',
                'assistance.status',
                'assistance.date',
                'assistance.reason',
            ])
            ->join('person', 'assistance.person_id', 'person.id')
            ->join('user', 'person.id', 'user.person_id')
            ->whereDate('assistance.created_at', $request->date)
            ->where('assistance.deleted_at', null)
            ->orderBy('person.names', 'asc')
            ->get();

        return $updatedAssistances;
    }
}
