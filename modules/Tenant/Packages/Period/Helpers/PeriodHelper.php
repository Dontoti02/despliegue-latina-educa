<?php

namespace Modules\Tenant\Packages\Period\Helpers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Tenant\Models\Enrollment;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Period;

class PeriodHelper
{
    public static function current($required = false)
    {
        $period = Period::select()
            ->where('is_current', true)
            ->first();

        if (!$period && $required) {
          throw new Exception('¡No se ha encontrado un periodo lectivo activo!');
        }

        if (!$period) {
            $period = Period::select()
                ->orderBy('id', 'desc')
                ->first();
        }

        return $period;
    }

    public static function validateListRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "page"      => "required|integer|gt:0",
            "size"      => "required|integer|gt:0",
            "search"    => "nullable|string",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function validateRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name"                              => "required|string",
            "start_date"                        => "required|date",
            "end_date"                          => "required|date|after:start_date",
            "enrollment_start_date"             => "required|date",
            "enrollment_end_date"               => "required|date|after:enrollment_start_date",
            "classroom_start_date"              => "required|date",
            "classroom_end_date"                => "required|date|after:classroom_start_date",
            "type_min_requirement_to_pass"      => "required|numeric|in:0,1,2",
            "min_requirement_to_pass"           => "required|numeric|min:0",
            "is_required_enrollment_payment"    => "required|boolean",
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }
    }

    public static function generateStatusForClosed(Period $period)
    {
        $participants = Participant::select([
            'participant.id',
            'study_plan_detail.study_plan_id',
            'study_plan_detail.cycle_id',
            'student.id as student_id',
            'course.credits',
            'participant.is_approved',
        ])
            ->join('student', function ($join) {
                $join
                    ->on('participant.person_id', 'student.person_id')
                    ->whereNull('student.deleted_at');
            })
            ->join('classroom', function ($join) use ($period) {
                $join
                    ->on('participant.classroom_id', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->where('classroom.period_id', $period->id);
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
            ->get();

        $enrollments = Enrollment::select([
            'enrollment.id',
            'student_plan.study_plan_id',
            'enrollment.cycle_id',
            'student_plan.student_id',
        ])
            ->join('student_plan', function ($join) {
                $join
                    ->on('enrollment.student_plan_id', 'student_plan.id')
                    ->whereNull('student_plan.deleted_at');
            })
            ->where('enrollment.period_id', $period->id)
            ->get();

        $typeMinRequirementToPass = $period->type_min_requirement_to_pass;
        $minRequirementToPass = $period->min_requirement_to_pass;

        $updatedEnrollments = [];

        foreach ($enrollments as $enrollment) {
            $isApproved = null;

            // Cantidad de cursos
            if ($typeMinRequirementToPass == 0) {
                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('is_approved', true)
                    ->count();

                $isApproved = $totalApprovedClassrooms >= $minRequirementToPass;
            }

            // Porcentaje de cursos
            if ($typeMinRequirementToPass == 1) {
                $totalClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('student_id', $enrollment->student_id)
                    ->count();

                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('is_approved', true)
                    ->count();

                $percentageApproved = $totalClassrooms > 0 ? ($totalApprovedClassrooms / $totalClassrooms) * 100 : 0;

                $isApproved = $percentageApproved >= $minRequirementToPass;
            }

            // Porcentaje de créditos
            if ($typeMinRequirementToPass == 2) {
                $sumCredits = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('student_id', $enrollment->student_id)
                    ->sum('credits');

                $sumCreditsApproved = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('is_approved', true)
                    ->sum('credits');

                $percentageApproved = $sumCredits > 0 ? ($sumCreditsApproved / $sumCredits) * 100 : 0;

                $isApproved = $percentageApproved >= $minRequirementToPass;
            }

            $updatedEnrollments[$enrollment->id] = [
                'id' => $enrollment->id,
                'is_approved' => $isApproved,
            ];
        }

        $updatedEnrollments = array_values($updatedEnrollments);

        foreach (array_chunk($updatedEnrollments, 1000) as $chunk) {
            $ids = [];
            $cases = [];

            foreach ($chunk as $item) {
                $id = $item['id'];
                $value = $item['is_approved'];

                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $ids[] = $id;
                $cases[] = "WHEN $id THEN $value";
            }

            $idsString = implode(',', $ids);
            $casesString = implode(' ', $cases);

            $sql = "
                UPDATE enrollment
                SET is_approved = CASE id
                    $casesString
                END
                WHERE id IN ($idsString)
            ";

            DB::update($sql);
        }
    }
}
