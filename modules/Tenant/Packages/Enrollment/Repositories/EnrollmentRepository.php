<?php

namespace Modules\Tenant\Packages\Enrollment\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Packages\Enrollment\Enums\EnrollmentTypeEnum;
use Modules\Tenant\Models\AdditionalData;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\Enrollment;
use Modules\Tenant\Models\EnrollmentType;
use Modules\Tenant\Models\Family;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\PaymentConcept;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\RolUser;
use Modules\Tenant\Models\Scale;
use Modules\Tenant\Models\SchoolData;
use Modules\Tenant\Models\Section;
use Modules\Tenant\Models\Shift;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\StudentPlan;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Classroom\Repositories\ClassroomRepository;
use Modules\Tenant\Packages\Enrollment\Helpers\EnrollmentHelper;
use Modules\Tenant\Packages\Enrollment\Templates\EnrollmentTemplate;
use Modules\Tenant\Packages\Treasury\Enum\PaymentConceptEnum;
use Modules\Tenant\Packages\User\Enums\RolTenant;

class EnrollmentRepository
{
    public static function filters()
    {
        $user = User::authenticated();

        $isStudent = $user->rol_id === RolTenant::STUDENT;

        $periods = Period::select('id', 'name', 'is_current')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($period) => [
                'id' => $period->id,
                'name' => $period->is_current ? $period->name . ' (Actual)' : $period->name,
            ]);

        $enrollmentTypes = EnrollmentType::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $studyPrograms = StudyProgram::select('id', 'name')
            ->when($isStudent, function ($query) use ($user) {
                $query->whereHas('studyPlans.studentPlans.student', function ($subquery) use ($user) {
                    $subquery->where('person_id', $user->person_id);
                });
            })
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $studyPlans = StudyPlan::select('id', 'name', 'study_program_id')
            ->when($isStudent, function ($query) use ($user) {
                $query->whereHas('studentPlans.student', function ($subquery) use ($user) {
                    $subquery->where('person_id', $user->person_id);
                });
            })
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        $shifts = Shift::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $cycles = Cycle::select('id', 'name')
            ->orderBy('order', 'asc')
            ->get();

        $sections = Section::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $scales = Scale::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result = [
            'periods' => $periods,
            'enrollment_types' => $enrollmentTypes,
            'study_programs' => $studyPrograms,
            'study_plans' => $studyPlans,
            'shifts' => $shifts,
            'cycles' => $cycles,
            'sections' => $sections,
            'scales' => $scales,
        ];

        return $result;
    }

    public static function list(Request $request)
    {
        $user = User::authenticated();

        $isStudent = $user->rol_id === RolTenant::STUDENT;

        EnrollmentHelper::validateListRequest($request);

        $page = $request->input('page');
        $size = $request->input('size');
        $studentId = $request->input('student_id');
        $periodId = $request->input('period_id');
        $enrollmentTypeId = $request->input('enrollment_type_id');
        $studyProgramId = $request->input('study_program_id');
        $studyPlanId = $request->input('study_plan_id');
        $shiftId = $request->input('shift_id');
        $cycleId = $request->input('cycle_id');
        $sectionId = $request->input('section_id');

        if ($isStudent) {
            $student = Student::select()
                ->where('person_id', $user->person_id)
                ->firstOrFail();

            $studentId = $student->id;
        }

        $currentPeriod = Period::select()
            ->where('is_current', true)
            ->first();

        $currentPeriodId = $currentPeriod ? $currentPeriod->id : null;

        $enrollments = Enrollment::select([
            'enrollment.id',
            'period.name as period_name',
            'person.document_number as student_document_number',
            'person.names as student_names',
            'study_program.name as study_program_name',
            'study_plan.name as study_plan_name',
            'cycle.name as cycle_name',
            'shift.name as shift_name',
            'section.name as section_name',
            'enrollment_type.id as enrollment_type_id',
            'enrollment_type.name as enrollment_type_name',
            'enrollment.registration_date',
            'enrollment.is_approved',
        ])
            ->join('period', function ($join) {
                $join
                    ->on('enrollment.period_id', '=', 'period.id')
                    ->whereNull('period.deleted_at');
            })
            ->join('student_plan', function ($join) {
                $join
                    ->on('enrollment.student_plan_id', '=', 'student_plan.id')
                    ->whereNull('student_plan.deleted_at');
            })
            ->join('student', function ($join) {
                $join
                    ->on('student_plan.student_id', '=', 'student.id')
                    ->whereNull('student.deleted_at');
            })
            ->join('person', function ($join) {
                $join
                    ->on('student.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('student_plan.study_plan_id', '=', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join('study_program', function ($join) {
                $join
                    ->on('study_plan.study_program_id', '=', 'study_program.id')
                    ->whereNull('study_program.deleted_at');
            })
            ->join('cycle', function ($join) {
                $join
                    ->on('enrollment.cycle_id', '=', 'cycle.id')
                    ->whereNull('cycle.deleted_at');
            })
            ->leftJoin('shift', function ($join) {
                $join
                    ->on('enrollment.shift_id', '=', 'shift.id')
                    ->whereNull('shift.deleted_at');
            })
            ->leftJoin('section', function ($join) {
                $join
                    ->on('enrollment.section_id', '=', 'section.id')
                    ->whereNull('section.deleted_at');
            })
            ->join('enrollment_type', function ($join) {
                $join
                    ->on('enrollment.type_id', '=', 'enrollment_type.id')
                    ->whereNull('enrollment_type.deleted_at');
            })
            ->when($studentId, function ($query) use ($studentId) {
                $query->where('student.id', $studentId);
            })
            ->when($periodId, function ($query) use ($periodId) {
                $query->where('period.id', $periodId);
            })
            ->when($enrollmentTypeId, function ($query) use ($enrollmentTypeId) {
                $query->where('enrollment_type.id', $enrollmentTypeId);
            })
            ->when($studyPlanId, function ($query) use ($studyPlanId) {
                $query->where('study_plan.id', $studyPlanId);
            })
            ->when($studyProgramId, function ($query) use ($studyProgramId) {
                $query->where('study_program.id', $studyProgramId);
            })
            ->when($shiftId, function ($query) use ($shiftId) {
                $query->where('shift.id', $shiftId);
            })
            ->when($cycleId, function ($query) use ($cycleId) {
                $query->where('cycle.id', $cycleId);
            })
            ->when($sectionId, function ($query) use ($sectionId) {
                $query->where('section.id', $sectionId);
            })
            ->orderBy('period.id', 'desc')
            ->orderBy('enrollment.registration_date', 'desc')
            ->orderBy('person.names', 'asc')
            ->paginate($size, ['*'], 'page', $page);

        $enrollmentsMap = [];
        foreach ($enrollments as $enrollment) {
            $status = null;

            if ($enrollment->is_approved === true) {
                $status = 'Aprobado';
            }

            if ($enrollment->is_approved === false) {
                $status = 'Desaprobado';
            }

            $isCurrent = $enrollment->period_id === $currentPeriodId;

            if ($isCurrent) {
                $status = 'En curso';
            }

            $enrollmentsMap[] = [
                'id' => $enrollment->id,
                'period_name' => $enrollment->period_name,
                'student_document_number' => $enrollment->student_document_number,
                'student_names' => $enrollment->student_names,
                'study_program_name' => $enrollment->study_program_name,
                'study_plan_name' => $enrollment->study_plan_name,
                'cycle_name' => $enrollment->cycle_name,
                'shift_name' => $enrollment->shift_name,
                'section_name' => $enrollment->section_name,
                'enrollment_type_id' => $enrollment->enrollment_type_id,
                'enrollment_type_name' => $enrollment->enrollment_type_name,
                'registration_date' => Carbon::parse($enrollment->registration_date)->format('Y-m-d'),
                'status' => $status,
                'is_editable' => true,
                'is_removed' => $isCurrent,
            ];
        }

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $enrollments->total(),
            'items' => $enrollmentsMap,
        ];

        return $result;
    }

    public static function get(int $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $studyPlan = $enrollment->studentPlan->studyPlan;
        $studyProgram = $studyPlan->studyProgram;

        $result = [
            'id' => $enrollment->id,
            'period_id' => $enrollment->period_id,
            'study_program_id' => $studyProgram->id,
            'study_plan_id' => $studyPlan->id,
            'cycle_id' => $enrollment->cycle_id,
            'shift_id' => $enrollment->shift_id,
            'section_id' => $enrollment->section_id,
            'is_approved' => $enrollment->is_approved,
            'registration_date' => Carbon::parse($enrollment->registration_date)->format('Y-m-d'),

            'observations' => $enrollment->observations,

            'scale_id' => $enrollment->scale_id,
            'scale_authorization_document_type' => $enrollment->scale_authorization_document_type,
            'scale_authorization_document_number' => $enrollment->scale_authorization_document_number,
            'scale_authorization_full_names' => $enrollment->scale_authorization_full_names,
        ];

        return $result;
    }

    public static function update(Request $request)
    {
        EnrollmentHelper::validateUpdateRequest($request);

        $id = $request->input('id');
        $isApproved = $request->input('is_approved');
        $registrationDate = $request->input('registration_date');

        $observations = $request->input('observations');

        $scaleId = $request->input('scale_id');
        $scaleAuthorizationDocumentType = $request->input('scale_authorization_document_type');
        $scaleAuthorizationDocumentNumber = $request->input('scale_authorization_document_number');
        $scaleAuthorizationFullNames = $request->input('scale_authorization_full_names');

        $enrollment = Enrollment::findOrFail($id);

        $enrollment->update([
            'is_approved' => $isApproved,
            'registration_date' => $registrationDate,

            'observations' => $observations,

            'scale_id' => $scaleId,
            'scale_authorization_document_type' => $scaleId ? $scaleAuthorizationDocumentType : null,
            'scale_authorization_document_number' => $scaleId ? $scaleAuthorizationDocumentNumber : null,
            'scale_authorization_full_names' => $scaleId ? $scaleAuthorizationFullNames : null,
        ]);

        return "Matricula actualizada correctamente";
    }

    public static function delete(int $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $periodId = $enrollment->period_id;
        $studentId = $enrollment->studentPlan->student_id;
        $studyPlanId = $enrollment->studentPlan->study_plan_id;
        $cycleId = $enrollment->cycle_id;
        $shiftId = $enrollment->shift_id;
        $sectionId = $enrollment->section_id;

        $currentPeriod = Period::select()
            ->where('is_current', true)
            ->firstOrFail();

        if ($currentPeriod->id != $periodId) {
            throw new Exception('No se puede eliminar una matrícula de un periodo lectivo diferente al actual');
        }

        $studentPlans = StudentPlan::select()
            ->where('student_id', $studentId)
            ->pluck('id')
            ->toArray();

        $enrollmentPrevious = Enrollment::select()
            ->whereNot('id', $id)
            ->whereIn('student_plan_id', $studentPlans)
            ->orderBy('id', 'desc')
            ->first();

        if ($enrollmentPrevious) {
            if ($enrollmentPrevious->studentPlan->study_plan_id != $studyPlanId) {
                $enrollment->studentPlan->delete();
            }
        }

        $enrollment->delete();

        // Eliminar al estudiante de todas las clases
        $classroomIds = Classroom::select()
            ->where('period_id', $periodId)
            ->whereHas('studyPlanDetail', function ($query) use ($studyPlanId, $cycleId) {
                $query
                    ->where('study_plan_id', $studyPlanId)
                    ->where('cycle_id', $cycleId);
            })
            ->where('shift_id', $shiftId)
            ->where('section_id', $sectionId)
            ->pluck('id')
            ->toArray();

        foreach ($classroomIds as $classroomId) {
            ClassroomRepository::remove($classroomId, $studentId);
        }

        return "Matricula eliminada correctamente";
    }

    public static function validate()
    {
        $errors = [];

        // Validar que existe un periodo lectivo actual
        $period = Period::select()
            ->where('is_current', true)
            ->first();

        if (!$period) {
            $errors[] = [
                'title' => 'No hay periodo lectivo activo',
                'caption' => 'Es necesario que tengas establecido un periodo lectivo activo.'
            ];
        }

        // Validar que se hayan configurado fechas en el periodo lectivo actual
        if (!$period->start_date || !$period->end_date) {
            $errors[] = [
                'title' => 'Fechas de periodo lectivo no configuradas',
                'caption' => 'Es necesario que tengas establecidas las fechas de inicio y fin del periodo lectivo activo, para poder generar las pensiones. Por favor, ve a la sección de <strong>Procesos Académicos > Periodos Lectivos</strong> y configura las fechas del periodo lectivo activo.'
            ];
        }

        // Validar que se hayan configurado fechas de matricula en el periodo lectivo actual
        if (!$period->enrollment_start_date || !$period->enrollment_end_date) {
            $errors[] = [
                'title' => 'Fechas de periodo lectivo no configuradas',
                'caption' => 'Es necesario que tengas establecidas las fechas de inicio y fin del registro de matriculas del periodo lectivo activo. Por favor, ve a la sección de <strong>Procesos Académicos > Periodos Lectivos</strong> y configura las fechas del registro de matriculas del periodo lectivo activo.'
            ];
        }

        // Validar que se hayan configurado fechas de clases en el periodo lectivo actual
        if (!$period->classroom_start_date || !$period->classroom_end_date) {
            $errors[] = [
                'title' => 'Fechas de periodo lectivo no configuradas',
                'caption' => 'Es necesario que tengas establecidas las fechas de inicio y fin de las clases del periodo lectivo activo. Por favor, ve a la sección de <strong>Procesos Académicos > Periodos Lectivos</strong> y configura las fechas de las clases del periodo lectivo activo.'
            ];
        }

        // Validar que estamos entre las fechas de matrícula del periodo lectivo actual
        $now = Carbon::now();

        if ($now->lt(Carbon::parse($period->enrollment_start_date)) || $now->gt(Carbon::parse($period->enrollment_end_date))) {
            $errors[] = [
                'title' => 'No estamos en fechas de matrícula',
                'caption' => 'Actualmente no estamos entre las fechas de matrícula del periodo lectivo activo. Por favor, verifica las fechas de matrícula del periodo lectivo activo en la sección de <strong>Procesos Académicos > Periodos Lectivos</strong>.'
            ];
        }

        // Validar que exista un concepto de pago de matricula registrado
        $paymentConcept = PaymentConcept::select()
            ->where('code', PaymentConceptEnum::MATRICULA_CONCEPT_CODE)
            ->exists();

        if (!$paymentConcept) {
            $errors[] = [
                'title' => 'Concepto de pago para matrícula no configurado',
                'caption' => 'Es necesario que tengas configurado el concepto de pago para matrícula. Por favor, ve a la sección de <strong>Tesorería > Conceptos de pago</strong> y configura el concepto de pago para matrícula.'
            ];
        }

        // Validar que exista un concepto de pago de pensión registrado
        $paymentConcept = PaymentConcept::select()
            ->where('code', PaymentConceptEnum::PENSIONES_CONCEPT_CODE)
            ->exists();

        if (!$paymentConcept) {
            $errors[] = [
                'title' => 'Concepto de pago para pensiones no configurado',
                'caption' => 'Es necesario que tengas configurado el concepto de pago para pensiones. Por favor, ve a la sección de <strong>Tesorería > Conceptos de pago</strong> y configura el concepto de pago para pensiones.'
            ];
        }

        return $errors;
    }

    public static function family(string $documentNumber)
    {
        $family = Family::select()
            ->where('document_number', $documentNumber)
            ->first();

        return $family;
    }

    public static function detail(Request $request)
    {
        EnrollmentHelper::validateDetailRequest($request);

        $type = $request->input('type');
        $studentId = $request->input('student_id');

        if ($type === 'incoming') {
            return self::detailIncoming($studentId);
        }

        if ($type === 'regular') {
            return self::detailRegular($studentId);
        }
    }

    private static function detailIncoming(int|null $studentId)
    {
        $result = [
            'periods' => [],
            'study_programs' => [],
            'scales' => [],
            'messages' => [],
        ];

        $currentPeriod = Period::select('id', 'name')
            ->where('is_current', true)
            ->first();

        if (!$currentPeriod) {
            $result['messages'][] = 'No se encontró un periodo lectivo actual para realizar la matrícula.';
            return $result;
        }

        $result['periods'][] = $currentPeriod;

        $studyPlanIdsOmitted = [];
        if ($studentId) {
            $studyPlanIdsOmitted = StudyPlan::select()
                ->whereHas('studentPlans', function ($query) use ($studentId) {
                    $query
                        ->where('student_id', $studentId)
                        ->whereHas('enrollments');
                })
                ->pluck('id')
                ->toArray();
        }

        $classrooms = Classroom::select([
            'study_program.id as study_program_id',
            'study_program.name as study_program_name',
            'study_plan.id as study_plan_id',
            'study_plan.name as study_plan_name',
            'cycle.id as cycle_id',
            'cycle.name as cycle_name',
            'shift.id as shift_id',
            'shift.name as shift_name',
            'section.id as section_id',
            'section.name as section_name',
        ])
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('study_plan_detail.study_plan_id', '=', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at')
                    ->where('study_plan.is_active', true);
            })
            ->join('study_program', function ($join) {
                $join
                    ->on('study_plan.study_program_id', '=', 'study_program.id')
                    ->whereNull('study_program.deleted_at')
                    ->where('study_program.is_active', true);
            })
            ->join('cycle', function ($join) {
                $join
                    ->on('study_plan_detail.cycle_id', '=', 'cycle.id')
                    ->whereNull('cycle.deleted_at')
                    ->where('cycle.order', 1);
            })
            ->join('shift', function ($join) {
                $join
                    ->on('classroom.shift_id', '=', 'shift.id')
                    ->whereNull('shift.deleted_at');
            })
            ->join('section', function ($join) {
                $join
                    ->on('classroom.section_id', '=', 'section.id')
                    ->whereNull('section.deleted_at');
            })
            ->where('classroom.period_id', $currentPeriod->id)
            ->when(count($studyPlanIdsOmitted) > 0, function ($query) use ($studyPlanIdsOmitted) {
                $query->whereNotIn('study_plan.id', $studyPlanIdsOmitted);
            })
            ->groupBy('study_program.id')
            ->groupBy('study_plan.id')
            ->groupBy('cycle.id')
            ->groupBy('shift.id')
            ->groupBy('section.id')
            ->orderBy('study_program.name', 'asc')
            ->orderBy('study_plan.name', 'asc')
            ->orderBy('cycle.order', 'asc')
            ->orderBy('shift.name', 'asc')
            ->orderBy('section.name', 'asc')
            ->get();

        if ($classrooms->count() === 0) {
            $result['messages'][] = 'No se encontraron clases disponibles para realizar la matrícula.';
            return $result;
        }

        $classroomsMap = [];

        $programs = $classrooms
            ->pluck('study_program_name', 'study_program_id')
            ->toArray();

        foreach ($programs as $programId => $programName) {
            $programAdd = [
                'id' => $programId,
                'name' => $programName,
                'plans' => [],
            ];

            $plans = $classrooms
                ->where('study_program_id', $programId)
                ->pluck('study_plan_name', 'study_plan_id')
                ->toArray();

            foreach ($plans as $planId => $planName) {
                $planAdd = [
                    'id' => $planId,
                    'name' => $planName,
                    'cycles' => [],
                ];

                $cycles = $classrooms
                    ->where('study_program_id', $programId)
                    ->where('study_plan_id', $planId)
                    ->pluck('cycle_name', 'cycle_id')
                    ->toArray();

                foreach ($cycles as $cycleId => $cycleName) {
                    $cycleAdd = [
                        'id' => $cycleId,
                        'name' => $cycleName,
                        'shifts' => [],
                    ];

                    $shifts = $classrooms
                        ->where('study_program_id', $programId)
                        ->where('study_plan_id', $planId)
                        ->where('cycle_id', $cycleId)
                        ->pluck('shift_name', 'shift_id')
                        ->toArray();

                    foreach ($shifts as $shiftId => $shiftName) {
                        $shiftAdd = [
                            'id' => $shiftId,
                            'name' => $shiftName,
                            'sections' => [],
                        ];

                        $sections = $classrooms
                            ->where('study_program_id', $programId)
                            ->where('study_plan_id', $planId)
                            ->where('cycle_id', $cycleId)
                            ->where('shift_id', $shiftId)
                            ->pluck('section_name', 'section_id')
                            ->toArray();

                        foreach ($sections as $sectionId => $sectionName) {
                            $sectionAdd = [
                                'id' => $sectionId,
                                'name' => $sectionName,
                            ];

                            $shiftAdd['sections'][] = $sectionAdd;
                        }

                        $cycleAdd['shifts'][] = $shiftAdd;
                    }

                    $planAdd['cycles'][] = $cycleAdd;
                }

                $programAdd['plans'][] = $planAdd;
            }

            $classroomsMap[] = $programAdd;
        }

        $result['study_programs'] = $classroomsMap;

        $scales = Scale::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result['scales'] = $scales;

        return $result;
    }

    private static function detailRegular(int $studentId)
    {
        $result = [
            'periods' => [],
            'study_programs' => [],
            'scales' => [],
            'messages' => [],
        ];

        $currentPeriod = Period::select('id', 'name')
            ->where('is_current', true)
            ->first();

        if (!$currentPeriod) {
            $result['messages'][] = 'No se encontró un periodo lectivo actual para realizar la matrícula.';
            return $result;
        }

        $result['periods'][] = $currentPeriod;

        $studentPlans = StudentPlan::select([
            'student_plan.id',
            'study_program.id as study_program_id',
            'study_program.name as study_program_name',
            'study_program.is_active as study_program_is_active',
            'study_plan.id as study_plan_id',
            'study_plan.name as study_plan_name',
            'study_plan.is_active as study_plan_is_active',
        ])
            ->join('study_plan', function ($join) {
                $join
                    ->on('student_plan.study_plan_id', '=', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join('study_program', function ($join) {
                $join
                    ->on('study_plan.study_program_id', '=', 'study_program.id')
                    ->whereNull('study_program.deleted_at');
            })
            ->where('student_plan.student_id', $studentId)
            ->orderBy('study_program.name', 'asc')
            ->orderBy('study_plan.name', 'asc')
            ->get();

        if ($studentPlans->count() === 0) {
            $result['messages'][] = 'El estudiante no se encuentra inscrito en ningún programa de estudios.';
            return $result;
        }

        $classrooms = Classroom::select([
            'study_plan.id as study_plan_id',
            'cycle.id as cycle_id',
            'cycle.name as cycle_name',
            'shift.id as shift_id',
            'shift.name as shift_name',
            'section.id as section_id',
            'section.name as section_name',
        ])
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('study_plan_detail.study_plan_id', '=', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join('cycle', function ($join) {
                $join
                    ->on('study_plan_detail.cycle_id', '=', 'cycle.id')
                    ->whereNull('cycle.deleted_at');
            })
            ->join('shift', function ($join) {
                $join
                    ->on('classroom.shift_id', '=', 'shift.id')
                    ->whereNull('shift.deleted_at');
            })
            ->join('section', function ($join) {
                $join
                    ->on('classroom.section_id', '=', 'section.id')
                    ->whereNull('section.deleted_at');
            })
            ->join('student_plan', function ($join) use ($studentId) {
                $join
                    ->on('study_plan.id', '=', 'student_plan.study_plan_id')
                    ->whereNull('student_plan.deleted_at')
                    ->where('student_plan.student_id', $studentId);
            })
            ->where('classroom.period_id', $currentPeriod->id)
            ->groupBy('study_plan.id')
            ->groupBy('cycle.id')
            ->groupBy('shift.id')
            ->groupBy('section.id')
            ->orderBy('study_plan.name', 'asc')
            ->orderBy('cycle.order', 'asc')
            ->orderBy('shift.name', 'asc')
            ->orderBy('section.name', 'asc')
            ->get();

        if ($classrooms->count() === 0) {
            $result['messages'][] = 'No se encontraron clases disponibles para los programas de estudios del estudiante en el periodo lectivo actual.';
            return $result;
        }

        $studentPlansMap = [];

        $programs = $studentPlans
            ->pluck('study_program_name', 'study_program_id')
            ->toArray();

        foreach ($programs as $programId => $programName) {
            $programAdd = [
                'id' => $programId,
                'name' => $programName,
                'plans' => [],
            ];

            $studentPlanByProgram = $studentPlans
                ->where('study_program_id', $programId)
                ->first();

            if (!$studentPlanByProgram->study_program_is_active) {
                $result['messages'][] = "El programa de estudios '{$studentPlanByProgram->study_program_name}' no se encuentra activo.";
                continue;
            }

            $plans = $studentPlans
                ->where('study_program_id', $programId)
                ->pluck('study_plan_name', 'study_plan_id')
                ->toArray();

            foreach ($plans as $planId => $planName) {
                $planAdd = [
                    'id' => $planId,
                    'name' => $planName,
                    'cycles' => [],
                ];

                $studentPlanByPlan = $studentPlans
                    ->where('study_program_id', $programId)
                    ->where('study_plan_id', $planId)
                    ->first();

                if (!$studentPlanByPlan->study_plan_is_active) {
                    $result['messages'][] = "El plan de estudios '{$studentPlanByPlan->study_plan_name}' no se encuentra activo.";
                    continue;
                }

                $lastEnrollment = Enrollment::select()
                    ->where('student_plan_id', $studentPlanByPlan->id)
                    ->orderBy('period_id', 'desc')
                    ->first();

                if (!$lastEnrollment) {
                    $result['messages'][] = "No se encontró una matrícula previa para el plan de estudios '{$studentPlanByPlan->study_plan_name}'.";
                    continue;
                }

                if ($lastEnrollment->period_id === $currentPeriod->id) {
                    $result['messages'][] = "El estudiante ya se encuentra matriculado en el periodo lectivo actual para el programa de estudios '{$studentPlanByPlan->study_program_name}'.";
                    continue;
                }

                $nextCycle = Cycle::select()
                    ->whereHas('studyPlanDetails', function ($query) use ($planId) {
                        $query->where('study_plan_id', $planId);
                    })
                    ->where('order', '>', $lastEnrollment->cycle->order)
                    ->orderBy('order', 'asc')
                    ->first();

                $cycles = $classrooms
                    ->where('study_plan_id', $planId)
                    ->pluck('cycle_name', 'cycle_id')
                    ->toArray();

                foreach ($cycles as $cycleId => $cycleName) {
                    $cycleNameAux = $cycleName;

                    if ($lastEnrollment->cycle_id === $cycleId) {
                        $cycleNameAux = $cycleName . " (Actual)";
                    }

                    if (!$lastEnrollment->is_approved) {
                        $cycleNameAux = $cycleName . " (Actual/Siguiente)";
                    }

                    if ($nextCycle && $nextCycle->id === $cycleId) {
                        $cycleNameAux = $cycleName . " (Siguiente)";
                    }

                    $cycleAdd = [
                        'id' => $cycleId,
                        'name' => $cycleNameAux,
                        'shifts' => [],
                    ];

                    $shifts = $classrooms
                        ->where('study_plan_id', $planId)
                        ->where('cycle_id', $cycleId)
                        ->pluck('shift_name', 'shift_id')
                        ->toArray();

                    foreach ($shifts as $shiftId => $shiftName) {
                        $shiftNameAux = $shiftName;

                        if ($lastEnrollment->shift_id === $shiftId) {
                            $shiftNameAux = $shiftName . " (Actual)";
                        }

                        $shiftAdd = [
                            'id' => $shiftId,
                            'name' => $shiftNameAux,
                            'sections' => [],
                        ];

                        $sections = $classrooms
                            ->where('study_plan_id', $planId)
                            ->where('cycle_id', $cycleId)
                            ->where('shift_id', $shiftId)
                            ->pluck('section_name', 'section_id')
                            ->toArray();

                        foreach ($sections as $sectionId => $sectionName) {
                            $sectionNameAux = $sectionName;

                            if ($lastEnrollment->section_id === $sectionId) {
                                $sectionNameAux = $sectionName . " (Actual)";
                            }

                            $sectionAdd = [
                                'id' => $sectionId,
                                'name' => $sectionNameAux,
                            ];

                            $shiftAdd['sections'][] = $sectionAdd;
                        }

                        $cycleAdd['shifts'][] = $shiftAdd;
                    }

                    $planAdd['cycles'][] = $cycleAdd;
                }

                if (count($planAdd['cycles']) > 0) {
                    $programAdd['plans'][] = $planAdd;
                }
            }

            if (count($programAdd['plans']) > 0) {
                $studentPlansMap[] = $programAdd;
            }
        }

        $result['study_programs'] = $studentPlansMap;

        $scales = Scale::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        $result['scales'] = $scales;

        return $result;
    }

    public static function listClassrooms(Request $request)
    {
        EnrollmentHelper::validateListClassroomsRequest($request);

        $studentId = $request->input('student_id');
        $periodId = $request->input('period_id');
        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $shiftId = $request->input('shift_id');
        $sectionId = $request->input('section_id');

        $period = Period::findOrFail($periodId);
        $studyPlan = StudyPlan::findOrFail($studyPlanId);
        $studyProgram = StudyProgram::findOrFail($studyPlan->study_program_id);
        $cycle = Cycle::findOrFail($cycleId);
        $shift = Shift::findOrFail($shiftId);
        $section = Section::findOrFail($sectionId);

        $classrooms = Classroom::select([
            'classroom.id',
            'course.name as course_name',
        ])
            ->join('study_plan_detail', function ($join) use ($studyPlanId, $cycleId) {
                $join
                    ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at')
                    ->where('study_plan_detail.study_plan_id', $studyPlanId)
                    ->where('study_plan_detail.cycle_id', $cycleId);
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', '=', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->where('classroom.period_id', $periodId)
            ->where('classroom.shift_id', $shiftId)
            ->where('classroom.section_id', $sectionId)
            ->orderBy('course.name', 'asc')
            ->get();

        $classroomsMap = [];

        foreach ($classrooms as $classroom) {
            $isApproved = false;
            $isEnrolled = false;

            if ($studentId) {
                $student = Student::findOrFail($studentId);

                $participant = Participant::select()
                    ->where('person_id', $student->person_id)
                    ->where('classroom_id', $classroom->id)
                    ->first();

                $scoreMinToPass = $studyPlan->score_min_to_pass ?? 10.5;

                $isApproved = $participant ? $participant->score >= $scoreMinToPass : false;

                $isEnrolled = $participant ? true : false;
            }

            $countParticipants = Participant::select()
                ->where('classroom_id', $classroom->id)
                ->count();

            $classroomsMap[] = [
                'id' => $classroom->id,
                'course_name' => $classroom->course_name,
                'is_approved' => $isApproved,
                'is_enrolled' => $isEnrolled,
                'count_participants' => $countParticipants,
            ];
        }

        $result = [
            'period' => $period->name,
            'study_program' => $studyProgram->name,
            'study_plan' => $studyPlan->name,
            'cycle' => $cycle->name,
            'shift' => $shift->name,
            'section' => $section->name,
            'classrooms' => $classroomsMap,
        ];

        return $result;
    }

    public static function set(Request $request)
    {
        EnrollmentHelper::validateSetRequest($request);

        $studentId = $request->input('student_id');

        $type = $request->input('type');
        $periodId = $request->input('period_id');
        $registrationDate = $request->input('registration_date');
        $studyPlanId = $request->input('study_plan_id');
        $cycleId = $request->input('cycle_id');
        $shiftId = $request->input('shift_id');
        $sectionId = $request->input('section_id');

        $observations = $request->input('observations');
        $isFullPayment = $request->input('is_full_payment');

        $scaleId = $request->input('scale_id');
        $scaleAuthorizationDocumentType = $request->input('scale_authorization_document_type');
        $scaleAuthorizationDocumentNumber = $request->input('scale_authorization_document_number');
        $scaleAuthorizationFullNames = $request->input('scale_authorization_full_names');

        $classroomIds = $request->input('classroom_ids', []);

        if (!$studentId) {
            // Si no se proporciona el ID del estudiante, se asume que se debe crear.
            $studentId = self::createStudent($request);
        }

        $period = Period::select()
            ->where('id', $periodId)
            ->where('is_current', true)
            ->first();

        if (!$period) {
            throw new Exception('No se puede crear una matrícula para un periodo lectivo diferente al actual');
        }

        $studyPlan = StudyPlan::findOrFail($studyPlanId);

        if (!$studyPlan->studyProgram->is_active) {
            throw new Exception('El programa de estudios seleccionado ya no está vigente');
        }

        if (!$studyPlan->is_active) {
            throw new Exception('El plan de estudios seleccionado ya no está vigente');
        }

        $enrollments = Enrollment::select([
            'enrollment.*',
            'study_plan.study_program_id',
            'student_plan.study_plan_id',
        ])
            ->join('student_plan', function ($join) use ($studentId) {
                $join
                    ->on('enrollment.student_plan_id', '=', 'student_plan.id')
                    ->whereNull('student_plan.deleted_at')
                    ->where('student_plan.student_id', $studentId);
            })
            ->join('study_plan', function ($join) {
                $join
                    ->on('student_plan.study_plan_id', '=', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->orderBy('enrollment.period_id', 'desc')
            ->get();

        $existsEnrollmentInPeriodAndPlan = $enrollments
            ->where('period_id', $periodId)
            ->where('study_plan_id', $studyPlanId)
            ->count() > 0;

        if ($existsEnrollmentInPeriodAndPlan) {
            throw new Exception('El estudiante ya se encuentra matriculado en el periodo lectivo y plan de estudios seleccionados.');
        }

        $typeId = null;
        $studentPlanId = null;

        if ($type === 'incoming') {
            $existsEnrollmentInProgram = $enrollments
                ->where('study_program_id', $studyPlan->study_program_id)
                ->count() > 0;

            if ($existsEnrollmentInProgram) {
                throw new Exception('El estudiante ya se encuentra matriculado en el programa de estudios seleccionado.');
            }

            $existsEnrollmentInPlan = $enrollments
                ->where('study_plan_id', $studyPlanId)
                ->count() > 0;

            if ($existsEnrollmentInPlan) {
                throw new Exception('El estudiante ya se encuentra matriculado en el plan de estudios seleccionado.');
            }

            $cycle = Cycle::select()
                ->orderBy('order', 'asc')
                ->firstOrFail();

            $typeId = EnrollmentTypeEnum::INGRESANTE;
            $cycleId = $cycle->id;

            $newStudentPlan = StudentPlan::create([
                'student_id' => $studentId,
                'study_plan_id' => $studyPlanId,
                'registration_date' => $registrationDate,
                'is_active' => true,
            ]);

            $studentPlanId = $newStudentPlan->id;
        }

        if ($type === 'regular') {
            $lastEnrollmentInPlan = $enrollments
                ->where('study_plan_id', $studyPlanId)
                ->first();

            if (!$lastEnrollmentInPlan) {
                throw new Exception('El estudiante no se encuentra matriculado en el plan de estudios seleccionado.');
            }

            if (!$lastEnrollmentInPlan->is_approved && $cycleId !== $lastEnrollmentInPlan->cycle_id) {
                throw new Exception('El estudiante no se puede matricular en el ciclo seleccionado porque no aprobó el ciclo de la última matrícula del plan de estudios seleccionado.');
            }

            $existsEnrollmentInCycleApproved = $enrollments
                ->where('study_plan_id', $studyPlanId)
                ->where('cycle_id', $cycleId)
                ->where('is_approved', true)
                ->count() > 0;

            if ($existsEnrollmentInCycleApproved) {
                throw new Exception('El estudiante ya aprobó el ciclo seleccionado en el plan de estudios seleccionado.');
            }

            $typeId = EnrollmentTypeEnum::REGULAR;
            $studentPlanId = $lastEnrollmentInPlan->student_plan_id;
        }

        Enrollment::create([
            'student_plan_id' => $studentPlanId,
            'type_id' => $typeId,
            'period_id' => $periodId,
            'cycle_id' => $cycleId,
            'shift_id' => $shiftId,
            'section_id' => $sectionId,
            'registration_date' => $registrationDate,

            'observations' => $observations,
            'is_full_payment' => $isFullPayment,

            'scale_id' => $scaleId,
            'scale_authorization_document_type' => $scaleId ? $scaleAuthorizationDocumentType : null,
            'scale_authorization_document_number' => $scaleId ? $scaleAuthorizationDocumentNumber : null,
            'scale_authorization_full_names' => $scaleId ? $scaleAuthorizationFullNames : null,
        ]);

        // Inscribir al estudiante en las clases seleccionadas
        $classroomIds = collect($classroomIds)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        foreach ($classroomIds as $classroomId) {
            ClassroomRepository::enroll($classroomId, $studentId);
        }

        return '¡Estudiante inscrito correctamente!';
    }

    private static function createStudent(Request $request)
    {
        // Datos personales
        $personDocumentNumber = $request->input('person.document_number');
        $personNames = $request->input('person.names');
        // $personBirthDate = $request->input('person.birth_date');
        $personBirthDate = Carbon::createFromFormat(
            'd/m/Y',
            $request->input('person.birth_date')
        )->format('Y-m-d');

        $personSex = $request->input('person.sex');
        $personCivilStatus = $request->input('person.civil_status');
        $personCountry = $request->input('person.country');
        $personDepartment = $request->input('person.department');
        $personProvince = $request->input('person.province');
        $personDistrict = $request->input('person.district');

        $contactCurrentAddress = $request->input('contact.current_address');
        $contactPermanentAddress = $request->input('contact.permanent_address');
        $contactCellphone = $request->input('contact.cellphone');
        $contactTelephone = $request->input('contact.telephone');
        $contactEmail = $request->input('contact.email');

        // Datos académicos
        $academicAdmissionDate = $request->input('academic.admission_date');
        $academicSchoolName = $request->input('academic.school_name');
        $academicModularCode = $request->input('academic.modular_code');
        $academicGraduationYear = $request->input('academic.graduation_year');
        $academicSchoolType = $request->input('academic.school_type');
        $academicSchoolCategory = $request->input('academic.school_category');
        $academicCEVACertificate = $request->input('academic.CEVA_certificate');
        $academicCondition = $request->input('academic.condition');
        $academicObservations = $request->input('academic.observations');
        $academicPhoto = $request->file('academic.photo');
        $academicValidation = $request->file('academic.validation');

        // Datos familiares
        $family = $request->input('family', []);

        $existsPerson = Person::select()
            ->where('document_number', $personDocumentNumber)
            ->exists();

        if ($existsPerson) {
            throw new Exception('El número de documento ya se encuentra registrado.');
        }

        $existsEmail = User::select()
            ->where('email', $contactEmail)
            ->exists();

        if ($existsEmail) {
            throw new Exception('El correo ya se encuentra registrado.');
        }

        $person = Person::create([
          'document_type' => strlen($personDocumentNumber) === 8 ? 'D.N.I' : 'C.E',
          'document_number' => $personDocumentNumber,
          'names' => $personNames,
          'phone' => $contactTelephone,
          'sex' => $personSex,
          'birth_date' => $personBirthDate,
        ]);

        AdditionalData::create([
            'person_id' => $person->id,
            'civil_status' => $personCivilStatus,
            'country' => $personCountry,
            'department' => $personDepartment,
            'province' => $personProvince,
            'district' => $personDistrict,
            'current_address' => $contactCurrentAddress,
            'permanent_address' => $contactPermanentAddress,
            'cell_phone' => $contactCellphone,
        ]);

        $photoPath = $academicPhoto->store('public/images');
        $pdfPath = $academicValidation ? $academicValidation->store('public/pdf') : null;

        SchoolData::create([
            'person_id' => $person->id,
            'modular_code' => $academicModularCode,
            'name' => $academicSchoolName,
            'start_date' => $academicAdmissionDate,
            'end_date' => $academicGraduationYear,
            'type' => $academicSchoolType,
            'category' => $academicSchoolCategory,
            'CEVA_certificate' => $academicCEVACertificate,
            'condition' => $academicCondition,
            'observations' => $academicObservations,
            'photo' => $photoPath,
            'academic_validation' => $pdfPath,
        ]);

        foreach ($family as $item) {
            Family::create([
                'person_id' => $person->id,
                'document_type' => $item['document_type'],
                'document_number' => $item['document_number'],
                'full_names' => $item['names'],
                'relationship' => $item['relationship'],
                'phone' => $item['telephone'] ?? null,
                'email' => $item['email'],
                'cell_phone' => $item['cellphone'] ?? null,
                'address' => $item['address'],
                'occupation' => $item['occupation'],
            ]);
        }

        $student = Student::create([
            'person_id' => $person->id,
        ]);

        $user = User::create([
            'person_id' => $person->id,
            'email' => $contactEmail,
            'password' => Hash::make($personDocumentNumber),
        ]);

        RolUser::create([
            'user_id' => $user->id,
            'rol_id' => RolTenant::STUDENT,
        ]);

        return $student->id;
    }

    public static function download(Request $request)
    {
        EnrollmentHelper::validateDownloadRequest($request);

        $type = $request->input('type');
        $enrollmentId = $request->input('enrollment_id');

        $enrollment = Enrollment::findOrFail($enrollmentId);

        $person = $enrollment->studentPlan->student->person;
        $personName = $person->document_number . ' - ' . $person->names;

        $periodName = $enrollment->period->name;

        $classrooms = Classroom::select([
            'classroom.id',
            'course.name as course',
            'person.names as teacher',
            'shift.name as shift',
            DB::raw("'INSCRITO' as status"),
        ])
            ->join('study_plan_detail', function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join('course', function ($join) {
                $join
                    ->on('study_plan_detail.course_id', '=', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->join('shift', function ($join) {
                $join
                    ->on('classroom.shift_id', '=', 'shift.id')
                    ->whereNull('shift.deleted_at');
            })
            ->leftJoin('teacher', function ($join) {
                $join
                    ->on('classroom.teacher_id', '=', 'teacher.id')
                    ->whereNull('teacher.deleted_at');
            })
            ->leftJoin('person', function ($join) {
                $join
                    ->on('teacher.person_id', '=', 'person.id')
                    ->whereNull('person.deleted_at');
            })
            ->join('participant', function ($join) use ($person) {
                $join
                    ->on('classroom.id', '=', 'participant.classroom_id')
                    ->whereNull('participant.deleted_at')
                    ->where('participant.person_id', $person->id);
            })
            ->where('classroom.period_id', $enrollment->period_id)
            ->orderBy('course.name', 'asc')
            ->get();

        $title = 'FICHA DE MATRICULA - ' . strtoupper($periodName);

        $columns = [
            'id' => '#',
            'course' => 'CURSO',
            'teacher' => 'PROFESOR',
            'shift' => 'TURNO',
            'status' => 'ESTADO',
        ];

        $columnsAligned = [
            'id',
            'shift',
            'status',
        ];

        $rows = $classrooms->toArray();

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionName = strtoupper($institutionName);

        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $applicationName = AdminSystemConfigurationHelper::getName();
        $applicationName = strtoupper($applicationName);

        $date = Carbon::now();
        $date = $date->isoFormat('dddd, D [de] MMMM [del] YYYY');

        $data = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'applicationName' => $applicationName,
            'title' => $title,
            'date' => $date,
            'student' => $personName,
            'period' => $periodName,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
            'document_type' => $person->document_type,
            'document_number' => $person->document_number,
        ];

        $template = EnrollmentTemplate::class;
        $view = 'exports/enrollment';

        if ($type === 'pdf') {
            return Generate::generatePdf($view, $data);
        }

        if ($type === 'xlsx') {
            return Generate::generateXlsx($template, $data);
        }
    }
}
