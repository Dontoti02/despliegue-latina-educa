<?php

namespace Modules\Tenant\Packages\Schedule\Helpers;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Enum\DaysOfWeek;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Schedule;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;

class ScheduleHelper
{
    public static function validateListRequest(Request $request, bool $isSecretary)
    {
        $required = $isSecretary ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "$required|numeric|exists:study_program,id",
            "cycle_id"          => "$required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListClassroomsRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "required|numeric|exists:study_program,id",
            "cycle_id"          => "required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListTeachersRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id" => "required|numeric|exists:period,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request, $update = false)
    {
        $required = $update ? "nullable" : "required";

        $validator = Validator::make($request->all(), [
            "classroom_id"  => "$required|numeric|exists:classroom,id",
            "day"           => "required|numeric|in:0,1,2,3,4,5,6",
            "hour_start"    => "required|string|size:5",
            "hour_end"      => "required|string|size:5",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateNotExistsSchedule(int $classroomId, int $day, string $hourStart, string $hourEnd, int $scheduleId = 0)
    {
        $schedule = Schedule::select()
            ->where('id', '!=', $scheduleId)
            ->where('classroom_id', $classroomId)
            ->where('day', $day)
            ->where(function ($query) use ($hourStart, $hourEnd) {
                $query
                    ->where('hour_start', '<', $hourEnd)
                    ->where('hour_end', '>', $hourStart);
            })
            ->exists();

        if ($schedule) {
            throw new Exception("El horario hace conflicto con otro horario existente");
        }
    }

    public static function validateHoursAndMinutes(string $hourStart, string $hourEnd)
    {
        $hours = SystemConfigurationHelper::getStudyHours();

        $start = Carbon::createFromFormat('H:i', $hours->start);
        $end = Carbon::createFromFormat('H:i', $hours->end);

        $hourStartDate = Carbon::createFromFormat('H:i', $hourStart);
        $hourEndDate = Carbon::createFromFormat('H:i', $hourEnd);

        $validMinutes = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];

        if (!in_array($hourStartDate->minute, $validMinutes)) {
            throw new Exception("Los minutos de la hora de inicio deben ser cada 5");
        }

        if (!in_array($hourEndDate->minute, $validMinutes)) {
            throw new Exception("Los minutos de la hora de fin deben ser cada 5");
        }

        if ($hourStartDate->lt($start)) {
            throw new Exception("La hora de inicio debe ser mayor o igual a la hora de inicio de clases");
        }

        if ($hourStartDate->gte($end)) {
            throw new Exception("La hora de inicio debe ser menor a la hora de fin de clases");
        }

        if ($hourStartDate->gte($hourEndDate)) {
            throw new Exception("La hora de inicio debe ser menor a la hora de fin");
        }

        if ($hourEndDate->gt($end)) {
            throw new Exception("La hora de fin debe ser menor o igual a la hora de fin de clases");
        }

        if ($hourStartDate->diffInMinutes($hourEndDate) < 45) {
            throw new Exception("Se acepta mínimo 45 minutos por clase diaria");
        }

        if ($hourStartDate->diffInMinutes($hourEndDate) > 120) {
            throw new Exception("Se acepta máximo 2 horas por clase diaria");
        }
    }

    public static function validateNotCross(Classroom $classroom, int $day, string $hourStart, string $hourEnd)
    {
        $hourStartDate = Carbon::createFromFormat('H:i', $hourStart);
        $hourEndDate = Carbon::createFromFormat('H:i', $hourEnd);

        $classrooms = Classroom::select([
            'classroom.id',
            'course.name as course_name',
            'schedule.hour_start',
            'schedule.hour_end',
        ])
            ->join('study_plan_detail', function ($join) use ($classroom) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at')
                    ->where('study_plan_detail.study_plan_id', $classroom->studyPlanDetail->study_plan_id)
                    ->where('study_plan_detail.cycle_id', $classroom->studyPlanDetail->cycle_id);
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->join('schedule', function ($join) {
                $join
                    ->on('classroom.id', 'schedule.classroom_id')
                    ->whereNull('schedule.deleted_at');
            })
            ->where('classroom.id', '!=', $classroom->id)
            ->where('classroom.period_id', $classroom->period_id)
            ->where('schedule.day', $day)
            ->get();

        foreach ($classrooms as $item) {
            $start = Carbon::createFromFormat('H:i', $item->hour_start);
            $end = Carbon::createFromFormat('H:i', $item->hour_end);

            if ($hourStartDate->lt($end) && $start->lt($hourEndDate)) {
                throw new Exception("El horario se cruza con la clase $item->course_name");
            }
        }
    }

    public static function validateAssignTeacherRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'classroom_id'  => 'required|integer|exists:classroom,id',
            'teacher_id'    => 'required|integer|exists:teacher,id',
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateNotCrossByTeacher(int $periodId, int $classroomId, int $teacherId)
    {
        $columns = [
            'schedule.id',
            'schedule.day',
            'schedule.hour_start',
            'schedule.hour_end',
            'course.name as course_name',
        ];

        $schedulesAssign = Schedule::select($columns)
            ->join('classroom', function ($join) use ($periodId, $teacherId) {
                $join
                    ->on('schedule.classroom_id', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.period_id', $periodId)
                    ->where('classroom.teacher_id', $teacherId);
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->get()
            ->toArray();

        $schedulesCurrent = Schedule::select($columns)
            ->join('classroom', function ($join) use ($classroomId) {
                $join
                    ->on('schedule.classroom_id', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.id', $classroomId);
            })
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->get()
            ->toArray();

        $schedules = array_merge($schedulesAssign, $schedulesCurrent);

        $days = [];
        foreach ($schedules as $schedule) {
            $start = Carbon::createFromFormat('H:i', $schedule['hour_start']);
            $end = Carbon::createFromFormat('H:i', $schedule['hour_end']);

            $hours = [];
            while ($start->lte($end)) {
                $hours[] = $start->format('H:i');
                $start->addMinutes(5);
            }

            array_pop($hours);

            $hours_of_day = $days[$schedule['day']] ?? [];

            $intersection = array_intersect($hours_of_day, $hours);

            if (!empty($intersection)) {
                $course = $schedule['course_name'];
                $day = DaysOfWeek::DAYS[$schedule['day']];
                throw new Exception("Al docente se le cruza el horario con la unidad didáctica $course el día $day");
            }

            $days[$schedule['day']] = array_merge($hours_of_day, $hours);
        }
    }

    public static function validateFiltersByExportRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "period_id"         => "required|numeric|exists:period,id",
            "study_program_id"  => "required|numeric|exists:study_program,id",
            "cycle_id"          => "required|numeric|exists:cycle,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateListByExportRequest(Request $request, bool $isSecretary)
    {
        $required = $isSecretary ? "required" : "nullable";

        $validator = Validator::make($request->all(), [
            "period_id"     => "required|numeric|exists:period,id",
            "rol_id"        => "$required|numeric|exists:rol,id",
            "person_id"     => "$required|numeric|exists:person,id",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }
}
