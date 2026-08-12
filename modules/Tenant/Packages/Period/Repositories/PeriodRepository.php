<?php

namespace Modules\Tenant\Packages\Period\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;

class PeriodRepository
{
    public static function list(Request $request)
    {
        PeriodHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $search = $request->input('search');

        $periods = Period::select()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->orderBy('name', 'desc')
            ->paginate($size, ['*'], 'page', $page);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $periods->total(),
            'items' => $periods->items(),
        ];

        return $result;
    }

    public static function create(Request $request)
    {
        PeriodHelper::validateRequest($request);

        $name = $request->input('name');
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        $enrollmentStartDate = $request->input('enrollment_start_date');
        $enrollmentEndDate = $request->input('enrollment_end_date');
        $classroomStartDate = $request->input('classroom_start_date');
        $classroomEndDate = $request->input('classroom_end_date');
        $typeMinRequirementToPass = $request->input('type_min_requirement_to_pass');
        $minRequirementToPass = $request->input('min_requirement_to_pass');
        $isRequiredEnrollmentPayment = $request->input('is_required_enrollment_payment');

        $existsPeriodName = Period::select()
            ->where('name', $name)
            ->exists();

        if ($existsPeriodName) {
            throw new Exception('Ya existe un periodo lectivo registrado con el mismo nombre.');
        }

        $existsPeriod = Period::select()
            ->where(function ($query) use ($startDate) {
                $query
                    ->where('start_date', '<=', $startDate)
                    ->where('end_date', '>=', $startDate);
            })
            ->orWhere(function ($query) use ($endDate) {
                $query
                    ->where('start_date', '<=', $endDate)
                    ->where('end_date', '>=', $endDate);
            })
            ->orWhere(function ($query) use ($startDate, $endDate) {
                $query
                    ->where('start_date', '>=', $startDate)
                    ->where('end_date', '<=', $endDate);
            })
            ->exists();

        if ($existsPeriod) {
            throw new Exception('Ya existe un periodo lectivo registrado dentro del rango de fechas.');
        }

        Period::create([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'enrollment_start_date' => $enrollmentStartDate,
            'enrollment_end_date' => $enrollmentEndDate,
            'classroom_start_date' => $classroomStartDate,
            'classroom_end_date' => $classroomEndDate,
            'type_min_requirement_to_pass' => $typeMinRequirementToPass,
            'min_requirement_to_pass' => $minRequirementToPass,
            'is_required_enrollment_payment' => $isRequiredEnrollmentPayment,
        ]);

        return 'Periodo Lectivo creado correctamente.';
    }

    public static function update(int $id, Request $request)
    {
        PeriodHelper::validateRequest($request);

        $period = Period::findOrFail($id);

        $name = $request->input('name');
        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
        $enrollmentStartDate = $request->input('enrollment_start_date');
        $enrollmentEndDate = $request->input('enrollment_end_date');
        $classroomStartDate = $request->input('classroom_start_date');
        $classroomEndDate = $request->input('classroom_end_date');
        $typeMinRequirementToPass = $request->input('type_min_requirement_to_pass');
        $minRequirementToPass = $request->input('min_requirement_to_pass');
        $isRequiredEnrollmentPayment = $request->input('is_required_enrollment_payment');

        $existsPeriodName = Period::select()
            ->where('id', '!=', $id)
            ->where('name', $name)
            ->exists();

        if ($existsPeriodName) {
            throw new Exception('Ya existe un periodo lectivo registrado con el mismo nombre.');
        }

        $existsPeriod = Period::select()
            ->where('id', '!=', $id)
            ->where(function ($query) use ($startDate, $endDate) {
                $query
                    ->where(function ($query) use ($startDate) {
                        $query
                            ->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $startDate);
                    })
                    ->orWhere(function ($query) use ($endDate) {
                        $query
                            ->where('start_date', '<=', $endDate)
                            ->where('end_date', '>=', $endDate);
                    })
                    ->orWhere(function ($query) use ($startDate, $endDate) {
                        $query
                            ->where('start_date', '>=', $startDate)
                            ->where('end_date', '<=', $endDate);
                    });
            })
            ->exists();

        if ($existsPeriod) {
            throw new Exception('Ya existe un periodo lectivo registrado dentro del rango de fechas.');
        }

        $period->update([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'enrollment_start_date' => $enrollmentStartDate,
            'enrollment_end_date' => $enrollmentEndDate,
            'classroom_start_date' => $classroomStartDate,
            'classroom_end_date' => $classroomEndDate,
            'type_min_requirement_to_pass' => $typeMinRequirementToPass,
            'min_requirement_to_pass' => $minRequirementToPass,
            'is_required_enrollment_payment' => $isRequiredEnrollmentPayment,
        ]);

        return 'Periodo Lectivo actualizado correctamente.';
    }

    public static function toggle(int $id)
    {
        $period = Period::findOrFail($id);

        $isCurrent = !$period->is_current;

        if (!$isCurrent) {
            $exists = Classroom::select()
                ->where('period_id', $id)
                ->where('is_closed', false)
                ->exists();

            if ($exists) {
                throw new Exception('No se puede finalizar el periodo lectivo porque tiene clases sin cerrar.');
            }

            PeriodHelper::generateStatusForClosed($period);
        } else {
            $currentPeriod = Period::select()
                ->where('id', '!=', $id)
                ->where('is_current', true)
                ->first();

            if ($currentPeriod) {
                throw new Exception("Se debe finalizar el periodo lectivo $currentPeriod->name antes de activar este.");
            }
        }

        $period->update([
            'is_current' => $isCurrent,
        ]);

        $message = $isCurrent ? 'activado' : 'finalizado';

        return "Periodo Lectivo $message correctamente.";
    }

    public static function delete(int $id)
    {
        $period = Period::findOrFail($id);

        if ($period->classrooms()->exists()) {
            throw new Exception('No se puede eliminar el periodo lectivo porque tiene clases asociadas');
        }

        if ($period->enrollments()->exists()) {
            throw new Exception('No se puede eliminar el periodo lectivo porque tiene matriculas asociadas');
        }

        $period->delete();

        return 'Periodo Lectivo eliminado correctamente.';
    }
}
