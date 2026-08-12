<?php

namespace Modules\Admin\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Admin\Helpers\ReadjustmentHelper;
use Modules\Admin\Queries\ReadjustmentQuery;
use Illuminate\Support\Facades\File;
use Modules\Admin\Models\Tenant;
use Modules\Tenant\Packages\Enrollment\Enums\EnrollmentTypeEnum;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;
use Modules\Tenant\Seeders\Options;

class ReadjustmentRepository
{
    public static function run()
    {
        set_time_limit(300);

        $log = [];

        $tenants = DB::table('tenant')->get();

        foreach ($tenants as $tenant) {
            $databaseName = $tenant->tenancy_db_name;

            $databaseExists = DB::selectOne('
                SELECT SCHEMA_NAME 
                FROM INFORMATION_SCHEMA.SCHEMATA 
                WHERE SCHEMA_NAME = ?
            ',  [$databaseName]);

            if (!$databaseExists) {
                continue;
            }

            $log[] = "$databaseName | Iniciando reajuste...";

            $result = self::readjustment($databaseName);

            $log = array_merge($log, $result);

            $log[] = "$databaseName | Reajuste finalizado.";
        }

        return $log;
    }

    private static function readjustment(string $databaseName)
    {
        $log = [];

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        $readjustmentCycles = DB::selectOne('
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ', [$databaseName, 'cycle', 'order']);

        if (!$readjustmentCycles) {
            $resultReadjustmentCycles = self::readjustmentCycles($databaseName);
            $log = array_merge($log, $resultReadjustmentCycles);
        }

        $existsPP = DB::selectOne('
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ', [$databaseName, 'program_plan']);

        if ($existsPP) {
            $resultPP = self::readjustmentProgramsAndPlans($databaseName);
            $log = array_merge($log, $resultPP);
        }

        $existsSPD = DB::selectOne('
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ', [$databaseName, 'study_plan_detail']);

        if (!$existsSPD) {
            $resultCTC = self::readjustmentCoursesAndTeachersAndClassrooms($databaseName);
            $log = array_merge($log, $resultCTC);
        }

        $existsP = DB::selectOne('
            SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
        ', [$databaseName, 'period', 'status']);

        if ($existsP) {
            $resultP = self::readjustmentPeriods($databaseName);
            $log = array_merge($log, $resultP);
        }

        $existsSP = DB::selectOne('
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
        ', [$databaseName, 'student_plan']);

        if (!$existsSP) {
            $resultSE = self::readjustmentStudentsAndEnrollments($databaseName);
            $log = array_merge($log, $resultSE);
        }

        $existsFP = DB::table("$databaseName.option")
            ->where('name_url', 'ProductiveFamily')
            ->exists();

        if (!$existsFP) {
            $resultO = self::readjustmentOptions($databaseName);
            $log = array_merge($log, $resultO);
        }

        $resultStatus = self::readjustmentStatus($databaseName);
        $log = array_merge($log, $resultStatus);

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        return $log;
    }

    private static function readjustmentCycles(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando ciclos...";

        $cycles = DB::table("$databaseName.cycle")
            ->orderBy('name', 'asc')
            ->get();

        $updatedCycles = [];
        foreach ($cycles as $index => $cycle) {
            $updatedCycles[] = [
                'id' => $cycle->id,
                'order' => $index + 1,
            ];
        }

        $data = [
            'updatedCycles' => $updatedCycles,
        ];

        $log[] = "$databaseName | Periodos Académicos procesados.";

        $result = ReadjustmentQuery::cycles($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentProgramsAndPlans(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando programas y planes de estudios...";

        $studyPrograms = DB::table("$databaseName.study_program")->get();
        $studyPlans = DB::table("$databaseName.study_plan")->get();
        $programsPlans = DB::table("$databaseName.program_plan")->get();

        $studyPlanTypeLastId = 0;
        $studyPlanLastId = 0;

        $createdStudyPlanTypes = [];
        $createdStudyPlans = [];
        foreach ($programsPlans as $programPlan) {
            $studyProgram = $studyPrograms
                ->where('id', $programPlan->study_program_id)
                ->first();

            $studyPlan = $studyPlans
                ->where('id', $programPlan->study_plan_id)
                ->first();

            $studyPlanTypeName = trim(preg_replace('/\s+/', ' ', $studyPlan->type));
            $studyPlanName = trim(preg_replace('/\s+/', ' ', $studyPlan->name));

            $existsStudyPlanType = collect($createdStudyPlanTypes)
                ->where('name', $studyPlanTypeName)
                ->first();

            if (!$existsStudyPlanType) {
                $studyPlanTypeLastId++;

                $existsStudyPlanType = [
                    'id' => $studyPlanTypeLastId,
                    'name' => $studyPlanTypeName,
                ];

                $createdStudyPlanTypes[] = $existsStudyPlanType;
            }

            $existsStudyPlan = collect($createdStudyPlans)
                ->where('study_program_id', $studyProgram->id)
                ->where('name', $studyPlanName)
                ->first();

            if (!$existsStudyPlan) {
                $studyPlanLastId++;

                $createdStudyPlans[] = [
                    'id' => $studyPlanLastId,
                    'study_program_id' => $studyProgram->id,
                    'type_id' => $existsStudyPlanType['id'],
                    'name' => $studyPlanName,
                    'year' => null,
                    'is_active' => true,
                    'score_min_to_pass' => 10.5,
                ];
            }
        }

        $data = [
            'createdStudyPlanTypes' => $createdStudyPlanTypes,
            'createdStudyPlans' => $createdStudyPlans,
        ];

        $log[] = "$databaseName | Programas y planes de estudios procesados.";

        $result = ReadjustmentQuery::programsAndPlans($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentCoursesAndTeachersAndClassrooms(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando ciclos, unidades didácticas, docentes y clases...";

        $studyPlans = DB::table("$databaseName.study_plan")->get();
        $cycles = DB::table("$databaseName.cycle")->get();
        $courses = DB::table("$databaseName.course")->get();
        $teachers = DB::table("$databaseName.teacher")->get();
        $classrooms = DB::table("$databaseName.classroom")->get();

        $staffsWorkingConditions = DB::table("$databaseName.staff")
            ->select('working_condition')
            ->distinct()
            ->pluck('working_condition')
            ->toArray();

        $workingConditionLastId = 0;
        $createdWorkingConditions = [];
        foreach ($staffsWorkingConditions as $staffsWorkingCondition) {
            $workingConditionLastId++;

            $createdWorkingConditions[] = [
                'id' => $workingConditionLastId,
                'name' => $staffsWorkingCondition,
            ];
        }

        $staffsTeachers = DB::table("$databaseName.staff")
            ->whereRaw("$databaseName.staff.id = (
                SELECT s.id
                FROM $databaseName.staff s
                WHERE
                    s.person_id = $databaseName.staff.person_id AND
                    s.type = 'DOCENTE'
                ORDER BY
                    s.period_id DESC
                LIMIT 1
            )")
            ->get();

        $teacherLastId = 0;
        $createdTeachers = [];
        foreach ($staffsTeachers as $staffTeacher) {
            $workingCondition = collect($createdWorkingConditions)
                ->where('name', $staffTeacher->working_condition)
                ->first();

            $existsTeacher = collect($createdTeachers)
                ->where('person_id', $staffTeacher->person_id)
                ->first();

            if (!$existsTeacher) {
                $teacherLastId++;

                $existsTeacher = [
                    'id' => $teacherLastId,
                    'person_id' => $staffTeacher->person_id,
                    'working_condition_id' => $workingCondition['id'],
                    'study_program_id' => null,
                    'registration_date' => $staffTeacher->registration_date,
                    'resolution_number' => null,
                ];

                $createdTeachers[] = $existsTeacher;
            }
        }

        $courseLastId = 0;
        $studyPlanDetailLastId = 0;
        $createdCourses = [];
        $createdStudyPlanDetails = [];
        $updatedClassrooms = [];
        foreach ($classrooms as $classroom) {
            $course = $courses
                ->where('id', $classroom->course_id)
                ->first();

            $studyPlansByProgram = $studyPlans
                ->where('study_program_id', $course->study_program_id)
                ->values();

            $studyPlan = ReadjustmentHelper::getBestStudyPlanByStudyProgram($studyPlansByProgram);

            $cycle = $cycles
                ->where('id', $course->cycle_id)
                ->first();

            $courseName = trim(preg_replace('/\s+/', ' ', $course->name));

            $existsCourse = collect($createdCourses)
                ->where('name', $courseName)
                ->first();

            if (!$existsCourse) {
                $courseLastId++;

                $existsCourse = [
                    'id' => $courseLastId,
                    'study_program_id' => null,
                    'module_id' => null,
                    'type_id' => null,
                    'code' => null,
                    'name' => $courseName,
                    'year' => null,
                    'credits' => null,
                    'hours' => null,
                    'description' => null,
                    'is_active' => true,
                ];

                $createdCourses[] = $existsCourse;
            }

            $existsStudyPlanDetail = collect($createdStudyPlanDetails)
                ->where('study_plan_id', $studyPlan->id)
                ->where('cycle_id', $cycle->id)
                ->where('course_id', $existsCourse['id'])
                ->first();

            if (!$existsStudyPlanDetail) {
                $studyPlanDetailLastId++;

                $existsStudyPlanDetail = [
                    'id' => $studyPlanDetailLastId,
                    'study_plan_id' => $studyPlan->id,
                    'cycle_id' => $cycle->id,
                    'course_id' => $existsCourse['id'],
                ];

                $createdStudyPlanDetails[] = $existsStudyPlanDetail;
            }

            $teacher = $teachers
                ->where('classroom_id', $classroom->id)
                ->first();

            $findTeacherId = $teacher
                ? collect($createdTeachers)
                    ->where('person_id', $teacher->person_id)
                    ->first()['id']
                : null;

            $updatedClassrooms[] = [
                'id' => $classroom->id,
                'study_plan_detail_id' => $existsStudyPlanDetail['id'],
                'teacher_id' => $findTeacherId,
            ];
        }

        $data = [
            'createdCourses' => $createdCourses,
            'createdStudyPlanDetails' => $createdStudyPlanDetails,
            'createdWorkingConditions' => $createdWorkingConditions,
            'createdTeachers' => $createdTeachers,
            'updatedClassrooms' => $updatedClassrooms,
        ];

        $log[] = "$databaseName | Periodos Académicos, unidades didácticas, docentes y clases procesados.";

        $result = ReadjustmentQuery::coursesAndTeachersAndClassrooms($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentPeriods(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando Periodos Lectivos...";

        $periods = DB::table("$databaseName.period")->get();

        $updatedPeriods = [];
        foreach ($periods as $period) {
            $updatedPeriods[] = [
                'id' => $period->id,
                'is_current' => $period->status == 'SIN TERMINO',
            ];
        }

        $data = [
            'updatedPeriods' => $updatedPeriods,
        ];

        $log[] = "$databaseName | Periodos Lectivos procesados.";

        $result = ReadjustmentQuery::periods($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentStudentsAndEnrollments(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando estudiantes y matriculas...";

        $studyPlans = DB::table("$databaseName.study_plan")->get();

        $oldStudents = DB::table("$databaseName.student")
            ->select()
            ->orderBy('person_id', 'asc')
            ->orderBy('period_id', 'desc')
            ->get();

        $oldEnrollments = DB::table("$databaseName.enrollment")->get();

        $cyclesOrderById = DB::table("$databaseName.cycle")
            ->pluck('order', 'id')
            ->toArray();

        $studentLastId = 0;
        $studentPlanLastId = 0;
        $enrollmentLastId = 0;

        $createdStudents = [];
        $createdStudentPlans = [];
        $createdEnrollments = [];
        foreach ($oldStudents as $oldStudent) {
            $student = collect($createdStudents)
                ->where('person_id', $oldStudent->person_id)
                ->first();

            if (!$student) {
                $studentLastId++;

                $student = [
                    'id' => $studentLastId,
                    'person_id' => $oldStudent->person_id,
                    'code' => null,
                ];

                $createdStudents[] = $student;
            }

            $studyPlansByProgram = $studyPlans
                ->where('study_program_id', $oldStudent->study_program_id)
                ->values();

            $studyPlan = ReadjustmentHelper::getBestStudyPlanByStudyProgram($studyPlansByProgram);

            $studentPlan = collect($createdStudentPlans)
                ->where('student_id', $student['id'])
                ->where('study_plan_id', $studyPlan->id)
                ->first();

            if (!$studentPlan) {
                $studentPlanLastId++;

                $studentPlan = [
                    'id' => $studentPlanLastId,
                    'student_id' => $student['id'],
                    'study_plan_id' => $studyPlan->id,
                    'registration_date' => $oldStudent->registration_date,
                    'is_active' => $oldStudent->status == 'ACTIVO',
                ];

                $createdStudentPlans[] = $studentPlan;
            }

            $enrollment = collect($createdEnrollments)
                ->where('student_plan_id', $studentPlan['id'])
                ->where('period_id', $oldStudent->period_id)
                ->first();

            if (!$enrollment) {
                $enrollmentLastId++;

                $enrollment = [
                    'id' => $enrollmentLastId,
                    'student_plan_id' => $studentPlan['id'],
                    'type_id' => $cyclesOrderById[$oldStudent->cycle_id] == 1 ? EnrollmentTypeEnum::INGRESANTE : EnrollmentTypeEnum::REGULAR,
                    'period_id' => $oldStudent->period_id,
                    'cycle_id' => $oldStudent->cycle_id,
                    'shift_id' => null,
                    'section_id' => null,
                    'is_approved' => null,
                    'registration_date' => $oldStudent->registration_date,

                    'observations' => null,
                    'is_full_payment' => null,

                    'scale_id' => null,
                    'scale_authorization_document_type' => null,
                    'scale_authorization_document_number' => null,
                    'scale_authorization_full_names' => null,
                ];

                $oldEnrollment = $oldEnrollments
                    ->where('person_id', $oldStudent->person_id)
                    ->where('study_program_id', $oldStudent->study_program_id)
                    ->where('period_id', $oldStudent->period_id)
                    ->first();

                if ($oldEnrollment) {
                    $enrollment['shift_id'] = $oldEnrollment->shift_id;
                    $enrollment['section_id'] = $oldEnrollment->section_id;
                    $enrollment['registration_date'] = $oldEnrollment->registration_date;

                    $enrollment['observations'] = $oldEnrollment->observations;
                    $enrollment['is_full_payment'] = $oldEnrollment->is_full_payment;

                    $enrollment['scale_id'] = $oldEnrollment->scale_id;
                    $enrollment['scale_authorization_document_type'] = $oldEnrollment->scale_authorization_document_type;
                    $enrollment['scale_authorization_document_number'] = $oldEnrollment->scale_authorization_document_number;
                    $enrollment['scale_authorization_full_names'] = $oldEnrollment->scale_authorization_full_names;
                }

                $createdEnrollments[] = $enrollment;
            }
        }

        $createdEnrollmentTypes = [
            [
                'id' => 1,
                'name' => 'Ingresante',
            ],
            [
                'id' => 2,
                'name' => 'Regular',
            ],
        ];

        $data = [
            'createdStudents' => $createdStudents,
            'createdStudentPlans' => $createdStudentPlans,
            'createdEnrollmentTypes' => $createdEnrollmentTypes,
            'createdEnrollments' => $createdEnrollments,
        ];

        $log[] = "$databaseName | Estudiantes y matriculas procesadas.";

        $result = ReadjustmentQuery::studentsAndEnrollments($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentOptions(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando opciones...";

        $info = Options::get($databaseName);

        $data = [
            'createdMenus' => $info['menus'],
            'createdOptions' => $info['options'],
            'createdRolOptions' => $info['rolOptions']
        ];

        $log[] = "$databaseName | Opciones procesadas.";

        $result = ReadjustmentQuery::options($databaseName, $data);

        $log = array_merge($log, $result);

        return $log;
    }

    private static function readjustmentStatus(string $databaseName)
    {
        $log = [];

        $log[] = "$databaseName | Procesando estado de clases, participantes y matriculas...";

        $periodIds = DB::table("$databaseName.period")
            ->where('is_current', false)
            ->pluck('id')
            ->toArray();

        $participants = DB::table("$databaseName.participant as participant")
            ->select([
                'participant.id',
                'study_plan_detail.study_plan_id',
                'study_plan.score_min_to_pass',
                'study_plan_detail.cycle_id',
                'classroom.period_id',
                'course.credits',
                'participant.classroom_id',
                'student.id as student_id',
                'participant.score',
            ])
            ->join("$databaseName.student as student", function ($join) {
                $join
                    ->on('participant.person_id', 'student.person_id')
                    ->whereNull('student.deleted_at');
            })
            ->join("$databaseName.classroom as classroom", function ($join) use ($periodIds) {
                $join
                    ->on('participant.classroom_id', 'classroom.id')
                    ->whereNull('classroom.deleted_at')
                    ->whereIn('classroom.period_id', $periodIds);
            })
            ->join("$databaseName.study_plan_detail as study_plan_detail", function ($join) {
                $join
                    ->on('classroom.study_plan_detail_id', 'study_plan_detail.id')
                    ->whereNull('study_plan_detail.deleted_at');
            })
            ->join("$databaseName.study_plan as study_plan", function ($join) {
                $join
                    ->on('study_plan_detail.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join("$databaseName.course as course", function ($join) {
                $join
                    ->on('study_plan_detail.course_id', 'course.id')
                    ->whereNull('course.deleted_at');
            })
            ->get();

        $updatedClassrooms = [];
        $updatedParticipants = [];

        foreach ($participants as $participant) {
            $updatedClassrooms[$participant->classroom_id] = [
                'id' => $participant->classroom_id,
                'is_closed' => true,
            ];

            $updatedParticipants[$participant->id] = [
                'id' => $participant->id,
                'is_approved' => $participant->score >= $participant->score_min_to_pass,
            ];
        }

        $enrollments = DB::table("$databaseName.enrollment as enrollment")
            ->select([
                'enrollment.id',
                'student_plan.study_plan_id',
                'study_plan.score_min_to_pass',
                'enrollment.cycle_id',
                'enrollment.period_id',
                'period.type_min_requirement_to_pass',
                'period.min_requirement_to_pass',
                'student_plan.student_id',
            ])
            ->join("$databaseName.student_plan as student_plan", function ($join) {
                $join
                    ->on('enrollment.student_plan_id', 'student_plan.id')
                    ->whereNull('student_plan.deleted_at');
            })
            ->join("$databaseName.study_plan as study_plan", function ($join) {
                $join
                    ->on('student_plan.study_plan_id', 'study_plan.id')
                    ->whereNull('study_plan.deleted_at');
            })
            ->join("$databaseName.period as period", function ($join) use ($periodIds) {
                $join
                    ->on('enrollment.period_id', 'period.id')
                    ->whereNull('period.deleted_at')
                    ->whereIn('period.id', $periodIds);
            })
            ->whereNull('enrollment.is_approved')
            ->get();

        $updatedEnrollments = [];

        foreach ($enrollments as $enrollment) {
            $isApproved = null;

            // Cantidad de cursos
            if ($enrollment->type_min_requirement_to_pass == 0) {
                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->count();

                $isApproved = $totalApprovedClassrooms >= $enrollment->min_requirement_to_pass;
            }

            // Porcentaje de cursos
            if ($enrollment->type_min_requirement_to_pass == 1) {
                $totalClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->count();

                $totalApprovedClassrooms = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->count();

                $percentageApproved = $totalClassrooms > 0 ? ($totalApprovedClassrooms / $totalClassrooms) * 100 : 0;

                $isApproved = $percentageApproved >= $enrollment->min_requirement_to_pass;
            }

            // Porcentaje de créditos
            if ($enrollment->type_min_requirement_to_pass == 2) {
                $sumCredits = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->sum('credits');

                $sumCreditsApproved = $participants
                    ->where('study_plan_id', $enrollment->study_plan_id)
                    ->where('cycle_id', $enrollment->cycle_id)
                    ->where('period_id', $enrollment->period_id)
                    ->where('student_id', $enrollment->student_id)
                    ->where('score', '>=', $enrollment->score_min_to_pass)
                    ->sum('credits');

                $percentageApproved = $sumCredits > 0 ? ($sumCreditsApproved / $sumCredits) * 100 : 0;

                $isApproved = $percentageApproved >= $enrollment->min_requirement_to_pass;
            }

            $updatedEnrollments[$enrollment->id] = [
                'id' => $enrollment->id,
                'is_approved' => $isApproved,
            ];
        }

        $updatedClassrooms = array_values($updatedClassrooms);
        $updatedParticipants = array_values($updatedParticipants);
        $updatedEnrollments = array_values($updatedEnrollments);

        $log[] = "$databaseName | Estado de clases, participantes y matriculas procesados.";

        ImportHelper::updateMassive($updatedClassrooms, "$databaseName.classroom", 'is_closed');
        ImportHelper::updateMassive($updatedParticipants, "$databaseName.participant", 'is_approved');
        ImportHelper::updateMassive($updatedEnrollments, "$databaseName.enrollment", 'is_approved');

        $log[] = "$databaseName | Estado de clases, participantes y matriculas actualizado.";

        return $log;
    }

    static function syncBaseMigrationsTenant() {
      set_time_limit(300);

    $log = [];
    $folderPath = base_path('modules/Tenant/migrations/feature-0001-base');
    if (!File::isDirectory($folderPath)) {
        $log[] = "ERROR: El directorio no existe -> {$folderPath}";
        return $log;
    }

    $files = File::files($folderPath);
    $migrationsToRegister = collect($files)->map(function ($file) {
        return pathinfo($file->getFilename(), PATHINFO_FILENAME);
    })->values()->all();

    if (empty($migrationsToRegister)) {
        $log[] = "No se encontraron archivos de migración en la carpeta especificada.";
        return $log;
    }

    $tenants = Tenant::all();

    foreach ($tenants as $tenant) {
        $databaseName = $tenant->tenancy_db_name;

        $databaseExists = DB::selectOne('
            SELECT SCHEMA_NAME 
            FROM INFORMATION_SCHEMA.SCHEMATA 
            WHERE SCHEMA_NAME = ?
        ', [$databaseName]);

        if (!$databaseExists) {
            $log[] = "{$databaseName} | La base de datos no existe. Omitiendo...";
            continue;
        }

        $log[] = "{$databaseName} | Iniciando reajuste...";

        try {
            tenancy()->initialize($tenant);
            $lastBatch = DB::connection('tenant')->table('migrations')->max('batch') ?? 0;
            $currentBatch = $lastBatch + 1;
            $existingMigrations = DB::connection('tenant')
                ->table('migrations')
                ->pluck('migration')
                ->toArray();

            $insertedCount = 0;

            foreach ($migrationsToRegister as $migrationName) {
                if (!in_array($migrationName, $existingMigrations)) {
                    DB::connection('tenant')->table('migrations')->insert([
                        'migration' => $migrationName,
                        'batch'     => $currentBatch,
                    ]);
                    $insertedCount++;
                }
            }

            $log[] = "{$databaseName} | Se registraron {$insertedCount} migraciones base.";
            $log[] = "{$databaseName} | Reajuste finalizado.";

        } catch (\Throwable $e) {
            $log[] = "{$databaseName} | ERROR al sincronizar: " . $e->getMessage();
        } finally {
            tenancy()->end();
        }
    }

    return $log;
  }
}
