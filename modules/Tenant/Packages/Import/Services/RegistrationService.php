<?php

namespace Modules\Tenant\Packages\Import\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Helpers\ReadjustmentHelper;
use Modules\Shared\Services\JsonFileService;
use Modules\Shared\Utils\Date;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\Enrollment;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\Rol;
use Modules\Tenant\Models\RolUser;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\StudentPlan;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Import\Enums\Status;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;

class RegistrationService
{
    private static function staging(array $data)
    {
        $title = '';

        $indexColumns = [];
        $records = [];

        foreach ($data as $indexRow => $row) {
            if (in_array($indexRow, [0, 1, 3])) {
                continue;
            }

            if ($indexRow == 2) {
                $title = $row[0];
                continue;
            }

            if ($indexRow == 4) {
                foreach ($row as $indexColumn => $column) {
                    $column = preg_replace('/\s+/', ' ', trim($column));

                    if (!$column) {
                        continue;
                    }

                    $indexColumns[$column] = $indexColumn;
                }

                continue;
            }

            $row = array_map(fn($item) => preg_replace('/\s+/', ' ', trim($item)), $row);

            if (array_filter($row) == []) {
                continue;
            }

            $birthDate = $row[$indexColumns['FECHA NACIMIENTO']];
            $birthDate = $birthDate ? Date::invertDateFormat($birthDate) : null;

            $registrationDate = $row[$indexColumns['FECHA REGISTRO']];
            $registrationDate = $registrationDate ? Date::invertDateTimeFormat($registrationDate) : null;

            $records[] = [
                'period_name' => $row[$indexColumns['LECTIVO']],
                'document_type' => $row[$indexColumns['TIPO DOCUMENTO']],
                'document_number' => $row[$indexColumns['DOCUMENTO']],
                'names' => implode(' ', [
                    $row[$indexColumns['APELLIDO PATERNO']],
                    $row[$indexColumns['APELLIDO MATERNO']],
                    $row[$indexColumns['NOMBRES']]
                ]),
                'birth_date' => $birthDate,
                'sex' => $row[$indexColumns['SEXO']],
                'email' => $row[$indexColumns['CORREO']],
                'phone' => $row[$indexColumns['CELULAR']],
                'native_language' => $row[$indexColumns['LENGUA MATERNA']],
                'study_program_name' => $row[$indexColumns['PROGRAMA DE ESTUDIOS']],
                'cycle_name' => $row[$indexColumns['CICLO']],
                'enrollment_status' => $row[$indexColumns['ESTADO MATRICULA']],
                'period_status' => $row[$indexColumns['ESTADO PERIODO']],
                'registration_date' => $registrationDate,
            ];
        }

        $result = [
            'title' => $title,
            'records' => $records,
        ];

        JsonFileService::write('registra_enrollment', $result);
    }

    private static function process()
    {
        $json = JsonFileService::read('registra_enrollment');

        $periodIds = Period::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $personIds = Person::all()
            ->keyBy(fn($item) => $item->document_number)
            ->map(fn($item) => $item->id);

        $emails = User::all()
            ->pluck('email')
            ->toArray();

        $userIds = User::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $rolIds = Rol::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $rolUserIds = RolUser::all()
            ->keyBy(fn($item) => implode('|', [
                $item->rol_id,
                $item->user_id,
            ]))
            ->map(fn($item) => $item->id);

        $studentIds = Student::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $studyProgramIds = StudyProgram::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studyPlansInDB = StudyPlan::all();

        $studyPlans = [];
        foreach ($studyProgramIds as $name => $id) {
            $studyPlansByProgram = $studyPlansInDB
                ->where('study_program_id', $id)
                ->values();

            $studyPlan = ReadjustmentHelper::getBestStudyPlanByStudyProgram($studyPlansByProgram);

            if (!$studyPlan) {
                continue;
            }

            $studyPlans[$name] = $studyPlan->id;
        }

        $cycleIds = Cycle::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studentPlanIds = StudentPlan::all()
            ->keyBy(fn($item) => implode('|', [
                $item->student_id,
                $item->study_plan_id,
            ]))
            ->map(fn($item) => $item->id);

        $enrollmentIds = Enrollment::all()
            ->keyBy(fn($item) => implode('|', [
                $item->student_plan_id,
                $item->period_id,
            ]))
            ->map(fn($item) => $item->id);

        $recordsMap = [];
        foreach ($json->records as $record) {
            $add = [
                'period_id' => null,
                'period_name' => $record->period_name,
                'period_status' => $record->period_status,
                'person_id' => null,
                'document_type' => $record->document_type,
                'document_number' => $record->document_number,
                'names' => $record->names,
                'phone' => $record->phone,
                'birth_date' => $record->birth_date,
                'sex' => $record->sex,
                'native_language' => $record->native_language,
                'user_id' => null,
                'email' => $record->email,
                'rol_id' => null,
                'rol_name' => 'ESTUDIANTE',
                'rol_user_id' => null,
                'student_id' => null,
                'study_program_id' => null,
                'study_program_name' => $record->study_program_name,
                'study_plan_id' => null,
                'cycle_id' => null,
                'cycle_name' => $record->cycle_name,
                'student_plan_id' => null,
                'enrollment_status' => $record->enrollment_status,
                'registration_date' => $record->registration_date,
                'enrollment_id' => null,
                'is_valid' => true,
                'status' => Status::NOT_REGISTERED,
            ];

            $periodId = $periodIds[$record->period_name] ?? null;

            if (!$periodId) {
                $add['is_valid'] = false;
                $add['status'] = 'El periodo lectivo no se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

            $add['period_id'] = $periodId;

            $personId = $personIds[$record->document_number] ?? null;

            $add['person_id'] = $personId;

            if (in_array($record->email, $emails) && !$personId) {
                $add['is_valid'] = false;
                $add['status'] = 'El correo ya se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

            $isDuplicateEmail = collect($recordsMap)
                ->where('email', $record->email)
                ->where('document_number', '!=', $record->document_number)
                ->where('is_valid', true)
                ->first();

            if ($isDuplicateEmail) {
                $add['is_valid'] = false;
                $add['status'] = 'El correo se encuentra duplicado en el archivo de importación.';
                $recordsMap[] = $add;
                continue;
            }

            $userId = $userIds[$personId] ?? null;

            $add['user_id'] = $userId;

            $rolId = $rolIds['ESTUDIANTE'] ?? null;

            $add['rol_id'] = $rolId;

            $rolUserId = $rolUserIds[implode('|', [
                $rolId,
                $userId,
            ])] ?? null;

            $add['rol_user_id'] = $rolUserId;

            $studentId = $studentIds[$personId] ?? null;

            $add['student_id'] = $studentId;

            $studyProgramId = $studyProgramIds[$record->study_program_name] ?? null;

            if (!$studyProgramId) {
                $add['is_valid'] = false;
                $add['status'] = 'El programa de estudios no se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

            $add['study_program_id'] = $studyProgramId;

            $studyPlanId = $studyPlans[$record->study_program_name] ?? null;

            if (!$studyPlanId) {
                $add['is_valid'] = false;
                $add['status'] = 'El programa de estudios no tiene un plan de estudios asociado.';
                $recordsMap[] = $add;
                continue;
            }

            $add['study_plan_id'] = $studyPlanId;

            $cycleId = $cycleIds[$record->cycle_name] ?? null;

            $add['cycle_id'] = $cycleId;

            $studentPlanId = $studentPlanIds[implode('|', [
                $studentId,
                $studyPlanId,
            ])] ?? null;

            $add['student_plan_id'] = $studentPlanId;

            $enrollmentId = $enrollmentIds[implode('|', [
                $studentPlanId,
                $periodId,
            ])] ?? null;

            $add['enrollment_id'] = $enrollmentId;

            if ($enrollmentId) {
                $add['status'] = Status::REGISTERED;
            }

            $recordsMap[] = $add;
        }

        $result = [
            'title' => $json->title,
            'records' => $recordsMap,
        ];

        JsonFileService::write('registra_enrollment_process', $result);
    }

    public static function show(array $data)
    {
        self::staging($data);
        self::process();

        $process = JsonFileService::read('registra_enrollment_process');

        $cycles = collect($process->records)
            ->unique(fn($item) => $item->cycle_name)
            ->sortBy('cycle_name')
            ->values()
            ->map(fn($item) => [
                $item->cycle_name,
                $item->cycle_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $enrollments = collect($process->records)
            ->values()
            ->map(fn($item) => [
                $item->period_name,
                $item->document_number,
                $item->names,
                $item->email,
                $item->study_program_name,
                $item->cycle_name,
                $item->status,
            ])
            ->prepend([
                'Periodo Lectivo',
                'DNI',
                'Nombres',
                'Correo',
                'Programa de estudios',
                'Periodo Académico',
                'Estado'
            ]);

        $result = [
            'title' => $process->title,
            'content' => [
                [
                    'name' => 'Periodos Académicos',
                    'items' => $cycles,
                ],
                [
                    'name' => 'Matriculas',
                    'items' => $enrollments,
                ],
            ]
        ];

        return $result;
    }

    public static function import(ImportDetail $importDetail, Carbon $now)
    {
        $process = JsonFileService::read('registra_enrollment_process');

        $records = collect($process->records)
            ->where('is_valid', true)
            ->values();

        $log = json_decode($importDetail->log);

        $newPersons = $records
            ->whereNull('person_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(fn($item) => [
                'document_type' => $item->document_type,
                'document_number' => $item->document_number,
                'names' => $item->names,
                'phone' => $item->phone,
                'birth_date' => $item->birth_date,
                'sex' => $item->sex,
                'native_language' => $item->native_language,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newPersons->toArray(), 'person');

        ImportHelper::progress($importDetail, $log, 10);

        $persons = Person::all()
            ->keyBy(fn($item) => $item->document_number)
            ->map(fn($item) => (object) [
                'id' => $item->id,
                'names' => $item->names
            ]);

        $newUsers = $records
            ->whereNull('user_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(function ($item) use ($persons, $now) {
                $personId = $item->person_id ?? $persons[$item->document_number]->id;

                ImportHelper::sendMail($persons[$item->document_number]->names, $item->email, $item->document_number);

                return [
                    'person_id' => $personId,
                    'email' => $item->email,
                    'password' => Hash::make($item->document_number),
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newUsers->toArray(), 'user');

        ImportHelper::progress($importDetail, $log, 20);

        $newRoles = $records
            ->whereNull('rol_id')
            ->unique(fn($item) => $item->rol_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->rol_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newRoles->toArray(), 'rol');

        ImportHelper::progress($importDetail, $log, 30);

        $users = User::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $roles = Rol::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newRolesUsers = $records
            ->whereNull('rol_user_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(function ($item) use ($roles, $persons, $users) {
                $rolId = $item->rol_id ?? $roles[$item->rol_name];
                $personId = $item->person_id ?? $persons[$item->document_number]->id;
                $userId = $item->user_id ?? $users[$personId];

                return [
                    'rol_id' => $rolId,
                    'user_id' => $userId,
                ];
            });

        ImportHelper::insert($newRolesUsers->toArray(), 'rol_user');

        ImportHelper::progress($importDetail, $log, 40);

        $newStudents = $records
            ->whereNull('student_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(function ($item) use ($persons, $now) {
                $personId = $item->person_id ?? $persons[$item->document_number]->id;

                return [
                    'person_id' => $personId,
                    'code' => null,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newStudents->toArray(), 'student');

        ImportHelper::progress($importDetail, $log, 50);

        $lastOrder = Cycle::max('order')->order ?? 1;

        $newCycles = $records
            ->whereNull('cycle_id')
            ->unique(fn($item) => $item->cycle_name)
            ->sortBy('cycle_name')
            ->values()
            ->map(fn($item) => [
                'name' => $item->cycle_name,
                'order' => $lastOrder++,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newCycles->toArray(), 'cycle');

        ImportHelper::progress($importDetail, $log, 60);

        $students = Student::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $newStudentPlans = $records
            ->whereNull('student_plan_id')
            ->unique(fn($item) => implode('|', [
                $item->document_number,
                $item->study_plan_id,
            ]))
            ->values()
            ->map(function ($item) use ($persons, $students, $now) {
                $personId = $item->person_id ?? $persons[$item->document_number]->id;
                $studentId = $item->student_id ?? $students[$personId];

                return [
                    'student_id' => $studentId,
                    'study_plan_id' => $item->study_plan_id,
                    'registration_date' => $item->registration_date,
                    'is_active' => $item->enrollment_status == 'ACTIVO',
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newStudentPlans->toArray(), 'student_plan');

        ImportHelper::progress($importDetail, $log, 70);

        $studentPlans = StudentPlan::all()
            ->keyBy(fn($item) => implode('|', [
                $item->student_id,
                $item->study_plan_id,
            ]))
            ->map(fn($item) => $item->id);

        $newEnrollments = $records
            ->whereNull('enrollment_id')
            ->unique(fn($item) => implode('|', [
                $item->document_number,
                $item->study_plan_id,
                $item->period_id,
            ]))
            ->values()
            ->map(function ($item) use ($persons, $students, $studentPlans, $now) {
                $personId = $item->person_id ?? $persons[$item->document_number]->id;
                $studentId = $item->student_id ?? $students[$personId];

                $studentPlanId = $item->student_plan_id ?? $studentPlans[implode('|', [
                    $studentId,
                    $item->study_plan_id,
                ])];

                return [
                    'student_plan_id' => $studentPlanId,
                    'type_id' => null,
                    'period_id' => $item->period_id,
                    'cycle_id' => $item->cycle_id,
                    'shift_id' => null,
                    'section_id' => null,
                    'is_approved' => null,
                    'registration_date' => $item->registration_date,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newEnrollments->toArray(), 'enrollment');

        ImportHelper::progress($importDetail, $log, 100);

        $summary = [
            'Total de ciclos importados' => $newCycles->count(),
            'Total de estudiantes importados' => $newStudents->count(),
            'Total de matriculas importadas' => $newEnrollments->count(),
        ];

        return $summary;
    }
}
