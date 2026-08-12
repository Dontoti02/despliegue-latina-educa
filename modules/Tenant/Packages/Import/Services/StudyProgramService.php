<?php

namespace Modules\Tenant\Packages\Import\Services;

use Carbon\Carbon;
use Modules\Shared\Services\JsonFileService;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\ProductiveFamily;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyPlanType;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Packages\Import\Enums\Status;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;

class StudyProgramService
{
    private static function staging(array $data)
    {
        $title = '';

        $indexColumns = [];
        $records = [];

        foreach ($data as $indexRow => $row) {
            if (in_array($indexRow, [0, 1])) {
                continue;
            }

            if ($indexRow == 2) {
                $title = $row[0];
                continue;
            }

            if ($indexRow == 3) {
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

            $records[] = [
                'period_name' => $row[$indexColumns['PERIODO LECTIVO']],
                'study_program_name' => $row[$indexColumns['PROGRAMA DE ESTUDIOS']],
                'productive_family_name' => $row[$indexColumns['FAMILIA PRODUCTIVA']],
                'study_plan_type_name' => $row[$indexColumns['TIPO PLAN']],
                'study_plan_name' => $row[$indexColumns['PLAN DE ESTUDIOS']],
            ];
        }

        $result = [
            'title' => $title,
            'records' => $records,
        ];

        JsonFileService::write('registra_study_program', $result);
    }

    private static function process()
    {
        $json = JsonFileService::read('registra_study_program');

        $productiveFamilyIds = ProductiveFamily::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studyProgramIds = StudyProgram::all()
            ->keyBy(fn($item) => implode('|', [
                $item->productive_family_id,
                $item->name
            ]))
            ->map(fn($item) => $item->id);

        $studyPlanTypeIds = StudyPlanType::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studyPlanIds = StudyPlan::all()
            ->keyBy(fn($item) => implode('|', [
                $item->study_program_id,
                $item->name
            ]))
            ->map(fn($item) => $item->id);

        $recordsMap = [];
        foreach ($json->records as $record) {
            $add = [
                'productive_family_id' => null,
                'productive_family_name' => $record->productive_family_name,
                'study_program_id' => null,
                'study_program_name' => $record->study_program_name,
                'study_plan_type_id' => null,
                'study_plan_type_name' => $record->study_plan_type_name,
                'study_plan_id' => null,
                'study_plan_name' => $record->study_plan_name,
                'is_valid' => true,
                'status' => Status::NOT_REGISTERED,
            ];

            $productiveFamilyId = $productiveFamilyIds[$record->productive_family_name] ?? null;

            $add['productive_family_id'] = $productiveFamilyId;

            $studyProgramId = $studyProgramIds[implode('|', [
                $productiveFamilyId,
                $record->study_program_name
            ])] ?? null;

            $add['study_program_id'] = $studyProgramId;

            $studyPlanTypeId = $studyPlanTypeIds[$record->study_plan_type_name] ?? null;

            $add['study_plan_type_id'] = $studyPlanTypeId;

            $studyPlanId = $studyPlanIds[implode('|', [
                $studyProgramId,
                $record->study_plan_name
            ])] ?? null;

            $add['study_plan_id'] = $studyPlanId;

            if ($studyPlanId) {
                $add['status'] = Status::REGISTERED;
            }

            $recordsMap[] = $add;
        }

        $result = [
            'title' => $json->title,
            'records' => $recordsMap,
        ];

        JsonFileService::write('registra_study_program_process', $result);
    }

    public static function show(array $data)
    {
        self::staging($data);
        self::process();

        $process = JsonFileService::read('registra_study_program_process');

        $productiveFamilies = collect($process->records)
            ->unique(fn($item) => $item->productive_family_name)
            ->values()
            ->map(fn($item) => [
                $item->productive_family_name,
                $item->productive_family_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $studyPrograms = collect($process->records)
            ->unique(fn($item) => implode('|', [
                $item->productive_family_name,
                $item->study_program_name
            ]))
            ->values()
            ->map(fn($item) => [
                $item->study_program_name,
                $item->productive_family_name,
                $item->study_program_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Familia productiva',
                'Estado'
            ]);

        $studyPlanTypes = collect($process->records)
            ->unique(fn($item) => $item->study_plan_type_name)
            ->values()
            ->map(fn($item) => [
                $item->study_plan_type_name,
                $item->study_plan_type_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $studyPlans = collect($process->records)
            ->unique(fn($item) => implode('|', [
                $item->study_program_name,
                $item->study_plan_name
            ]))
            ->values()
            ->map(fn($item) => [
                $item->study_plan_name,
                $item->study_program_name,
                $item->study_plan_type_name,
                $item->status
            ])
            ->prepend([
                'Nombre',
                'Programa de estudio',
                'Tipo',
                'Estado'
            ]);

        $result = [
            'title' => $process->title,
            'content' => [
                [
                    'name' => 'Familias productivas',
                    'items' => $productiveFamilies,
                ],
                [
                    'name' => 'Programas de estudio',
                    'items' => $studyPrograms,
                ],
                [
                    'name' => 'Tipos de plan de estudio',
                    'items' => $studyPlanTypes,
                ],
                [
                    'name' => 'Planes de estudio',
                    'items' => $studyPlans,
                ],
            ]
        ];

        return $result;
    }

    public static function import(ImportDetail $importDetail, Carbon $now)
    {
        $process = JsonFileService::read('registra_study_program_process');

        $records = collect($process->records)
            ->where('is_valid', true)
            ->values();

        $log = json_decode($importDetail->log);

        $newProductiveFamilies = $records
            ->whereNull('productive_family_id')
            ->unique(fn($item) => $item->productive_family_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->productive_family_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newProductiveFamilies->toArray(), 'productive_family');

        ImportHelper::progress($importDetail, $log, 10);

        $productiveFamilies = ProductiveFamily::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newStudyPrograms = $records
            ->whereNull('study_program_id')
            ->unique(fn($item) => implode('|', [
                $item->productive_family_name,
                $item->study_program_name
            ]))
            ->values()
            ->map(function ($item) use ($productiveFamilies, $now) {
                $productiveFamilyId = $item->productive_family_id ?? $productiveFamilies[$item->productive_family_name];

                return [
                    'productive_family_id' => $productiveFamilyId,
                    'name' => $item->study_program_name,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newStudyPrograms->toArray(), 'study_program');

        ImportHelper::progress($importDetail, $log, 20);

        $newStudyPlanTypes = $records
            ->whereNull('study_plan_type_id')
            ->unique(fn($item) => $item->study_plan_type_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->study_plan_type_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newStudyPlanTypes->toArray(), 'study_plan_type');

        ImportHelper::progress($importDetail, $log, 30);

        $studyPrograms = StudyProgram::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studyPlanTypes = StudyPlanType::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newStudyPlans = $records
            ->whereNull('study_plan_id')
            ->unique(fn($item) => implode('|', [
                $item->study_program_name,
                $item->study_plan_name
            ]))
            ->values()
            ->map(function ($item) use ($studyPrograms, $studyPlanTypes, $now) {
                $studyProgramId = $item->study_program_id ?? $studyPrograms[$item->study_program_name];
                $studyPlanTypeId = $item->study_plan_type_id ?? $studyPlanTypes[$item->study_plan_type_name];

                return [
                    'study_program_id' => $studyProgramId,
                    'type_id' => $studyPlanTypeId,
                    'name' => $item->study_plan_name,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newStudyPlans->toArray(), 'study_plan');

        ImportHelper::progress($importDetail, $log, 100);

        $summary = [
            'Total de Familias Productivas importadas' => $newProductiveFamilies->count(),
            'Total de Programas de Estudio importados' => $newStudyPrograms->count(),
            'Total de Tipos de Plan de Estudio importados' => $newStudyPlanTypes->count(),
            'Total de Planes de Estudio importados' => $newStudyPlans->count(),
        ];

        return $summary;
    }
}
