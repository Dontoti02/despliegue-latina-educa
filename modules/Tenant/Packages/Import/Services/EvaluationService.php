<?php

namespace Modules\Tenant\Packages\Import\Services;

use Carbon\Carbon;
use Modules\Admin\Helpers\ReadjustmentHelper;
use Modules\Shared\Services\JsonFileService;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Cycle;
use Modules\Tenant\Models\ImportDetail;
use Modules\Tenant\Models\Participant;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\Person;
use Modules\Tenant\Models\Section;
use Modules\Tenant\Models\Shift;
use Modules\Tenant\Models\Student;
use Modules\Tenant\Models\StudyPlan;
use Modules\Tenant\Models\StudyPlanDetail;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Packages\Import\Enums\Status;
use Modules\Tenant\Packages\Import\Helpers\ImportHelper;

class EvaluationService
{
    public static function staging(array $data)
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

            $score = $row[$indexColumns['NOTA']];
            $score = is_numeric($score) ? (float) $score : 0.00;

            $records[] = [
                'period_name' => $row[$indexColumns['LECTIVO']],
                'document_type' => $row[$indexColumns['TIPO DOCUMENTO']],
                'document_number' => $row[$indexColumns['DOCUMENTO']],
                'names' => implode(' ', [
                    $row[$indexColumns['APELLIDO PATERNO']],
                    $row[$indexColumns['APELLIDO MATERNO']],
                    $row[$indexColumns['NOMBRES']]
                ]),
                'email' => $row[$indexColumns['CORREO']],
                'phone' => $row[$indexColumns['CELULAR']],
                'study_program_name' => $row[$indexColumns['PROGRAMA DE ESTUDIOS']],
                'section_name' => $row[$indexColumns['SECCIÓN']],
                'shift_name' => $row[$indexColumns['TURNO']],
                'cycle_name' => $row[$indexColumns['PERIODO ACADÉMICO']],
                'course_name' => $row[$indexColumns['UNIDAD DIDÁCTICA']],
                'score' => $score,
            ];
        }

        $result = [
            'title' => $title,
            'records' => $records,
        ];

        JsonFileService::write('registra_evaluation', $result);
    }

    private static function process()
    {
        $json = JsonFileService::read('registra_evaluation');

        $periodIds = Period::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $personIds = Person::all()
            ->keyBy(fn($item) => $item->document_number)
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

        $courseIds = Course::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $studyPlanDetailIds = StudyPlanDetail::all()
            ->keyBy(fn($item) => implode('|', [
                $item->study_plan_id,
                $item->cycle_id,
                $item->course_id,
            ]))
            ->map(fn($item) => $item->id);

        $shiftIds = Shift::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $sectionIds = Section::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $classroomIds = Classroom::all()
            ->keyBy(fn($item) => implode('|', [
                $item->period_id,
                $item->study_plan_detail_id,
                $item->shift_id,
                $item->section_id,
            ]))
            ->map(fn($item) => $item->id);

        $participantIds = Participant::all()
            ->keyBy(fn($item) => implode('|', [
                $item->student_id,
                $item->classroom_id,
            ]))
            ->map(fn($item) => $item->id);

        $recordsMap = [];
        foreach ($json->records as $record) {
            $add = [
                'period_id' => null,
                'period_name' => $record->period_name,
                'person_id' => null,
                'document_number' => $record->document_number,
                'names' => $record->names,
                'student_id' => null,
                'study_program_id' => null,
                'study_program_name' => $record->study_program_name,
                'study_plan_id' => null,
                'cycle_id' => null,
                'cycle_name' => $record->cycle_name,
                'course_id' => null,
                'course_name' => $record->course_name,
                'study_plan_detail_id' => null,
                'shift_id' => null,
                'shift_name' => $record->shift_name,
                'section_id' => null,
                'section_name' => $record->section_name,
                'classroom_id' => null,
                'participant_id' => null,
                'score' => $record->score,
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

            if (!$personId) {
                $add['is_valid'] = false;
                $add['status'] = 'El estudiante no se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

            $add['person_id'] = $personId;

            $studentId = $studentIds[$personId] ?? null;

            if (!$studentId) {
                $add['is_valid'] = false;
                $add['status'] = 'El estudiante no se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

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

            if (!$cycleId) {
                $add['is_valid'] = false;
                $add['status'] = 'El ciclo no se encuentra registrado.';
                $recordsMap[] = $add;
                continue;
            }

            $add['cycle_id'] = $cycleId;

            $courseId = $courseIds[$record->course_name] ?? null;

            $add['course_id'] = $courseId;

            $studyPlanDetailId = $studyPlanDetailIds[implode('|', [
                $studyPlanId,
                $cycleId,
                $courseId,
            ])] ?? null;

            $add['study_plan_detail_id'] = $studyPlanDetailId;

            $shiftId = $shiftIds[$record->shift_name] ?? null;

            $add['shift_id'] = $shiftId;

            $sectionId = $sectionIds[$record->section_name] ?? null;

            $add['section_id'] = $sectionId;

            $classroomId = $classroomIds[implode('|', [
                $periodId,
                $studyPlanDetailId,
                $shiftId,
                $sectionId,
            ])] ?? null;

            $add['classroom_id'] = $classroomId;

            $participantId = $participantIds[implode('|', [
                $studentId,
                $classroomId,
            ])] ?? null;

            $add['participant_id'] = $participantId;

            if ($participantId) {
                $add['status'] = Status::REGISTERED;
            }

            $recordsMap[] = $add;
        }

        $result = [
            'title' => $json->title,
            'records' => $recordsMap,
        ];

        JsonFileService::write('registra_evaluation_process', $result);
    }

    public static function show(array $data)
    {
        self::staging($data);
        self::process();

        $process = JsonFileService::read('registra_evaluation_process');

        $courses = collect($process->records)
            ->unique(fn($item) => $item->course_name)
            ->sortBy('course_name')
            ->values()
            ->map(fn($item) => [
                $item->course_name,
                $item->course_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $shifts = collect($process->records)
            ->unique(fn($item) => $item->shift_name)
            ->sortBy('shift_name')
            ->values()
            ->map(fn($item) => [
                $item->shift_name,
                $item->shift_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $sections = collect($process->records)
            ->unique(fn($item) => $item->section_name)
            ->sortBy('section_name')
            ->values()
            ->map(fn($item) => [
                $item->section_name,
                $item->section_id ? Status::REGISTERED : Status::NOT_REGISTERED,
            ])
            ->prepend([
                'Nombre',
                'Estado'
            ]);

        $evaluations = collect($process->records)
            ->values()
            ->map(fn($item) => [
                $item->period_name,
                $item->document_number,
                $item->names,
                $item->study_program_name,
                $item->cycle_name,
                $item->course_name,
                $item->shift_name,
                $item->section_name,
                $item->score,
                $item->status,
            ])
            ->prepend([
                'Periodo Lectivo',
                'DNI',
                'Nombres',
                'Programa de estudios',
                'Periodo Académico',
                'Unidad Didáctica',
                'Turno',
                'Sección',
                'Nota',
                'Estado',
            ]);

        $result = [
            'title' => $process->title,
            'content' => [
                [
                    'name' => 'Unidades Didácticas',
                    'items' => $courses,
                ],
                [
                    'name' => 'Turnos',
                    'items' => $shifts,
                ],
                [
                    'name' => 'Secciones',
                    'items' => $sections,
                ],
                [
                    'name' => 'Evaluaciones',
                    'items' => $evaluations,
                ],
            ]
        ];

        return $result;
    }

    public static function import(ImportDetail $importDetail, Carbon $now)
    {
        $process = JsonFileService::read('registra_evaluation_process');

        $records = collect($process->records)
            ->where('is_valid', true)
            ->values();

        $log = json_decode($importDetail->log);

        $newCourses = $records
            ->whereNull('course_id')
            ->unique(fn($item) => $item->course_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->course_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newCourses->toArray(), 'course');

        ImportHelper::progress($importDetail, $log, 10);

        $courses = Course::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newStudyPlanDetails = $records
            ->whereNull('study_plan_detail_id')
            ->unique(fn($item) => implode('|', [
                $item->study_plan_id,
                $item->cycle_id,
                $item->course_name,
            ]))
            ->values()
            ->map(function ($item) use ($courses, $now) {
                $courseId = $item->course_id ?? $courses[$item->course_name];

                return [
                    'study_plan_id' => $item->study_plan_id,
                    'cycle_id' => $item->cycle_id,
                    'course_id' => $courseId,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newStudyPlanDetails->toArray(), 'study_plan_detail');

        ImportHelper::progress($importDetail, $log, 20);

        $newShifts = $records
            ->whereNull('shift_id')
            ->unique(fn($item) => $item->shift_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->shift_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newShifts->toArray(), 'shift');

        ImportHelper::progress($importDetail, $log, 30);

        $newSections = $records
            ->whereNull('section_id')
            ->unique(fn($item) => $item->section_name)
            ->values()
            ->map(fn($item) => [
                'name' => $item->section_name,
                'created_at' => $now,
            ]);

        ImportHelper::insert($newSections->toArray(), 'section');

        ImportHelper::progress($importDetail, $log, 40);

        $studyPlanDetails = StudyPlanDetail::all()
            ->keyBy(fn($item) => implode('|', [
                $item->study_plan_id,
                $item->cycle_id,
                $item->course_id,
            ]))
            ->map(fn($item) => $item->id);

        $shifts = Shift::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $sections = Section::all()
            ->keyBy(fn($item) => $item->name)
            ->map(fn($item) => $item->id);

        $newClassrooms = $records
            ->whereNull('classroom_id')
            ->unique(fn($item) => implode('|', [
                $item->period_id,
                $item->study_plan_id,
                $item->cycle_id,
                $item->course_name,
                $item->shift_name,
                $item->section_name,
            ]))
            ->values()
            ->map(function ($item) use ($courses, $studyPlanDetails, $shifts, $sections, $now) {
                $courseId = $item->course_id ?? $courses[$item->course_name];

                $studyPlanDetailId = $item->study_plan_detail_id ?? $studyPlanDetails[implode('|', [
                    $item->study_plan_id,
                    $item->cycle_id,
                    $courseId,
                ])];

                $shiftId = $item->shift_id ?? $shifts[$item->shift_name];

                $sectionId = $item->section_id ?? $sections[$item->section_name];

                return [
                    'period_id' => $item->period_id,
                    'study_plan_detail_id' => $studyPlanDetailId,
                    'shift_id' => $shiftId,
                    'section_id' => $sectionId,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newClassrooms->toArray(), 'classroom');

        ImportHelper::progress($importDetail, $log, 50);

        $classrooms = Classroom::all()
            ->keyBy(fn($item) => implode('|', [
                $item->period_id,
                $item->study_plan_detail_id,
                $item->shift_id,
                $item->section_id,
            ]))
            ->map(fn($item) => $item->id);

        $newParticipants = $records
            ->whereNull('participant_id')
            ->unique(fn($item) => implode('|', [
                $item->student_id,
                $item->period_id,
                $item->study_plan_id,
                $item->cycle_id,
                $item->course_name,
                $item->shift_name,
                $item->section_name,
            ]))
            ->values()
            ->map(function ($item) use ($courses, $studyPlanDetails, $shifts, $sections, $classrooms, $now) {
                $courseId = $item->course_id ?? $courses[$item->course_name];

                $studyPlanDetailId = $item->study_plan_detail_id ?? $studyPlanDetails[implode('|', [
                    $item->study_plan_id,
                    $item->cycle_id,
                    $courseId,
                ])];

                $shiftId = $item->shift_id ?? $shifts[$item->shift_name];

                $sectionId = $item->section_id ?? $sections[$item->section_name];

                $classroomId = $item->classroom_id ?? $classrooms[implode('|', [
                    $item->period_id,
                    $studyPlanDetailId,
                    $shiftId,
                    $sectionId,
                ])];

                return [
                    'student_id' => $item->student_id,
                    'classroom_id' => $classroomId,
                    'score' => $item->score,
                    'created_at' => $now,
                ];
            });

        ImportHelper::insert($newParticipants->toArray(), 'participant');

        ImportHelper::progress($importDetail, $log, 100);

        $summary = [
            'Total de unidades didácticas importadas' => $newCourses->count(),
            'Total de turnos importados' => $newShifts->count(),
            'Total de secciones importadas' => $newSections->count(),
            'Total de clases importadas' => $newClassrooms->count(),
            'Total de notas importadas' => $newParticipants->count(),
        ];

        return $summary;
    }
}
