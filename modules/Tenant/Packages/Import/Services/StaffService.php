<?php

namespace Modules\Tenant\Packages\Import\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Modules\Shared\Services\JsonFileService;
use Modules\Shared\Utils\Date;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\Rol;
use Modules\Tenant\Models\RolUser;
use Modules\Tenant\Models\Teacher;
use Modules\Tenant\Models\User;
use Modules\Tenant\Models\WorkingCondition;
use Modules\Tenant\Packages\Import\Enums\Status;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;

class StaffService
{
    private static function staging(array $data)
    {
        $title = '';

        $indexColumns = [];
        $records = [];

        foreach ($data as $indexRow => $row) {
            if (in_array($indexRow, [0, 1, 2])) {
                continue;
            }

            if ($indexRow == 3) {
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

            $registrationDate = $row[$indexColumns['FECHA REGISTRO']];
            $registrationDate = $registrationDate ? Date::invertDateFormat($registrationDate) : null;

            $records[] = [
                'period_name' => $row[$indexColumns['PERIODO LECTIVO']],
                'document_type' => $row[$indexColumns['TIPO DOCUMENTO']],
                'document_number' => $row[$indexColumns['DOCUMENTO']],
                'names' => $row[$indexColumns['NOMBRES Y APELLIDOS']],
                'type' => $row[$indexColumns['TIPO PERSONAL']],
                'phone' => $row[$indexColumns['CELULAR']],
                'email' => $row[$indexColumns['CORREO']],
                'registration_date' => $registrationDate,
                'working_condition_name' => $row[$indexColumns['CONDICIÓN LABORAL']],
            ];
        }

        $result = [
            'title' => $title,
            'records' => $records,
        ];

        JsonFileService::write('registra_staff', $result);
    }

    private static function process()
    {
        $json = JsonFileService::read('registra_staff');

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
                $item->user_id
            ]))
            ->map(fn($item) => $item->id);

        $workingConditionIds = WorkingCondition::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $teacherIds = Teacher::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $recordsMap = [];
        foreach ($json->records as $record) {
            $add = [
                'period_id' => null,
                'period_name' => $record->period_name,
                'person_id' => null,
                'document_type' => $record->document_type,
                'document_number' => $record->document_number,
                'names' => $record->names,
                'phone' => $record->phone,
                'user_id' => null,
                'email' => $record->email,
                'rol_id' => null,
                'rol_name' => $record->type,
                'rol_user_id' => null,
                'working_condition_id' => null,
                'working_condition_name' => $record->working_condition_name,
                'teacher_id' => null,
                'registration_date' => $record->registration_date,
                'is_valid' => true,
                'status' => Status::NOT_REGISTERED,
            ];

            $periodId = $periodIds[$record->period_name] ?? null;

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

            $rolId = $rolIds[$record->type] ?? null;

            $add['rol_id'] = $rolId;

            $rolUserId = $rolUserIds[implode('|', [
                $rolId,
                $userId,
            ])] ?? null;

            $add['rol_user_id'] = $rolUserId;

            $workingConditionId = $workingConditionIds[$record->working_condition_name] ?? null;

            $add['working_condition_id'] = $workingConditionId;

            if ($record->type == 'DOCENTE') {
                $teacherId = $teacherIds[$personId] ?? null;

                $add['teacher_id'] = $teacherId;
            }

            if ($rolUserId) {
                $add['status'] = Status::REGISTERED;
            }

            $recordsMap[] = $add;
        }

        $result = [
            'title' => $json->title,
            'records' => $recordsMap,
        ];

        JsonFileService::write('registra_staff_process', $result);
    }

    public static function show(array $data)
    {
        self::staging($data);
        self::process();

        $process = JsonFileService::read('registra_staff_process');

        $periods = collect($process->records)
            ->unique(fn($item) => $item->period_name)
            ->sortBy('period_name')
            ->values()
            ->map(fn($item) => [
                $item->period_name,
                $item->period_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $getRoles = function (string $roleName) use ($process) {
            return collect($process->records)
                ->where('rol_name', $roleName)
                ->unique(fn($item) => implode('|', [
                    $item->document_number,
                    $item->rol_name,
                ]))
                ->values()
                ->map(fn($item) => [
                    $item->document_number,
                    $item->names,
                    $item->email,
                    $item->status,
                ])
                ->prepend([
                    'DNI',
                    'Nombres',
                    'Correo',
                    'Estado'
                ]);
        };

        $directors = $getRoles('DIRECTOR GENERAL');
        $secretaries = $getRoles('SECRETARIO ACADÉMICO');
        $teachers = $getRoles('DOCENTE');

        $result = [
            'title' => $process->title,
            'content' => [
                [
                    'name' => 'Periodos Lectivos',
                    'items' => $periods,
                ],
                [
                    'name' => 'Director General',
                    'items' => $directors,
                ],
                [
                    'name' => 'Secretario Académico',
                    'items' => $secretaries,
                ],
                [
                    'name' => 'Docentes',
                    'items' => $teachers,
                ]
            ]
        ];

        return $result;
    }

    public static function import(ImportDetail $importDetail, Carbon $now)
    {
        $process = JsonFileService::read('registra_staff_process');

        $records = collect($process->records)
            ->where('is_valid', true)
            ->values();

        $log = json_decode($importDetail->log);

        $newPeriods = $records
            ->whereNull('period_id')
            ->unique(fn($item) => $item->period_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->period_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newPeriods->toArray(), 'period');

        ImportHelper::progress($importDetail, $log, 10);

        $newPersons = $records
            ->whereNull('person_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(fn($item) => [
                'document_type' => $item->document_type,
                'document_number' => $item->document_number,
                'names' => $item->names,
                'phone' => $item->phone,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newPersons->toArray(), 'person');

        ImportHelper::progress($importDetail, $log, 20);

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

        ImportHelper::progress($importDetail, $log, 30);

        $newRoles = $records
            ->whereNull('rol_id')
            ->unique(fn($item) => $item->rol_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->rol_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newRoles->toArray(), 'rol');

        ImportHelper::progress($importDetail, $log, 40);

        $users = User::all()
            ->keyBy(fn($item) => $item->person_id)
            ->map(fn($item) => $item->id);

        $roles = Rol::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newRolesUsers = $records
            ->whereNull('rol_user_id')
            ->unique(fn($item) => implode('|', [
                $item->document_number,
                $item->rol_name,
            ]))
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

        ImportHelper::progress($importDetail, $log, 50);

        $newWorkingConditions = $records
            ->whereNull('working_condition_id')
            ->unique(fn($item) => $item->working_condition_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->working_condition_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newWorkingConditions->toArray(), 'working_condition');

        ImportHelper::progress($importDetail, $log, 60);

        $workingConditions = WorkingCondition::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newTeachers = $records
            ->where('rol_name', 'DOCENTE')
            ->whereNull('teacher_id')
            ->unique(fn($item) => $item->document_number)
            ->values()
            ->map(function ($item) use ($persons, $workingConditions, $now) {
                $personId = $item->person_id ?? $persons[$item->document_number]->id;
                $workingConditionId = $item->working_condition_id ?? $workingConditions[$item->working_condition_name];

                return [
                    'person_id' => $personId,
                    'working_condition_id' => $workingConditionId,
                    'registration_date' => $item->registration_date,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newTeachers->toArray(), 'teacher');

        ImportHelper::progress($importDetail, $log, 100);

        $summary = [
            'Total de Periodos Lectivos importados' => $newPeriods->count(),
            'Total de personas importadas' => $newPersons->count(),
        ];

        return $summary;
    }
}
