<?php

namespace Modules\Tenant\Packages\Export\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Packages\Export\Templates\AbsencesTemplate;
use Modules\Tenant\Packages\Export\Templates\AttendanceConsolidatedTemplate;
use Modules\Tenant\Packages\Export\Templates\ConsolidatedRegistrationTemplate;
use Modules\Tenant\Packages\Export\Templates\ContentListTemplate;
use Modules\Tenant\Packages\Export\Templates\FinalNotesTemplate;
use Modules\Tenant\Packages\Export\Templates\PaymentConceptsTemplate;
use Modules\Tenant\Packages\Export\Templates\RegistrationTemplate;
use Modules\Tenant\Packages\Schedule\Repositories\ScheduleRepository;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Packages\Training\Repositories\TrainingAssistanceRepository;
use Modules\Tenant\Packages\Training\Repositories\TrainingContentRepository;
use Modules\Tenant\Packages\Training\Repositories\TrainingEvaluationRepository;
use Modules\Tenant\Models\PaymentConcept;
use Modules\Tenant\Packages\Assistance\Repositories\AssistanceRepository;
use Modules\Tenant\Packages\Content\Repositories\ContentRepository;
use Modules\Tenant\Packages\Evaluation\Repositories\EvaluationRepository;
use Modules\Tenant\Packages\Registration\Repositories\RegistrationRepository;

class ExportRepository
{
    public static function execute(string $key, string $type, Request $request)
    {
        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionName = strtoupper($institutionName);

        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $applicationName = AdminSystemConfigurationHelper::getName();
        $applicationName = strtoupper($applicationName);

        $date = Carbon::now();
        $date = $date->isoFormat('dddd, D [de] MMMM [del] YYYY');

        $info = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'applicationName' => $applicationName,
            'date' => $date,
        ];

        switch ($key) {
            case 'schedule':
                [$data, $template, $view] = self::exportSchedule($request, $info);
                break;
            case 'absences':
                [$data, $template, $view] = self::exportAbsences($request, $info);
                break;
            case 'capacitation-absences':
                [$data, $template, $view] = self::exportCapacitationAbsences($request, $info);
                break;
            case 'attendance-consolidated':
                [$data, $template, $view] = self::exportAttendanceConsolidated($request, $info);
                break;
            case 'capacitation-attendance-consolidated':
                [$data, $template, $view] = self::exportCapacitationAttendanceConsolidated($request, $info);
                break;
            case 'consolidated-notes':
                [$data, $template, $view] = self::exportFinalNotes($request, $info);
                break;
            case 'capacitation-consolidated-notes':
                [$data, $template, $view] = self::exportCapacitationFinalNotes($request, $info);
                break;
            case 'content-list':
                [$data, $template, $view] = self::exportContentList($request, $info);
                break;
            case 'capacitation-content-list':
                [$data, $template, $view] = self::exportCapacitationContentList($request, $info);
                break;
            case 'payment-concepts':
                [$data, $template, $view] = self::exportPaymentConcepts($request, $info);
                break;
            default:
                throw new Exception('Key no admitida');
        }

        function ensureNotNull($value, $errorMessage)
        {
            if (!$value) {
                throw new Exception($errorMessage);
            }
        }

        switch ($type) {
            case 'xlsx':
                ensureNotNull($template, 'Formato no disponible');
                $result = Generate::generateXlsx($template, $data);
                break;
            case 'pdf':
                ensureNotNull($view, 'Formato no disponible');
                $result = Generate::generatePdf($view, $data);
                break;
            default:
                throw new Exception('Tipo de exportación no admitido');
        }

        return $result;
    }

    public static function exportSchedule(Request $request, object $info)
    {
        $result = ScheduleRepository::listByExport($request);

        $title = 'HORARIO DE CLASES';

        $days = SystemConfigurationHelper::getStudyDays();

        $hours = SystemConfigurationHelper::getStudyHours();
        $hour_start = Carbon::createFromFormat('H:i', $hours->start);
        $hour_end = Carbon::createFromFormat('H:i', $hours->end);

        $columns = ['hour' => 'Hora'] + $days;

        function generarColorSuave()
        {
            $r = mt_rand(160, 255);
            $g = mt_rand(160, 255);
            $b = mt_rand(160, 255);

            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }

        $daysMap = [];
        foreach ($result->list as $a) {
            $aName = $a['name'];
            $aDays = $a['days'];
            $color = generarColorSuave();

            foreach ($aDays as $b) {
                $bDay = $b['day'];
                $bHourStart = $b['hour_start'];
                $bHourEnd = $b['hour_end'];

                $add = [
                    'name' => $bHourStart . ' - ' . $bHourEnd . '<br>' . $aName,
                    'color' => $color,
                    'hour_start' => $bHourStart,
                    'hour_end' => $bHourEnd,
                ];

                $daysMap[$bDay][] = $add;
            }
        }

        $hoursMap = [];
        while ($hour_start->lte($hour_end)) {
            $hour = $hour_start->format('H:i');

            foreach ($days as $numberDay => $nameDay) {
                $hoursMap[$hour][$numberDay] = [];

                if (isset($daysMap[$numberDay])) {
                    foreach ($daysMap[$numberDay] as $c) {
                        if ($hour >= $c['hour_start'] && $hour <= $c['hour_end']) {
                            $hoursMap[$hour][$numberDay] = [$c['name'], $c['color']];
                        }
                    }
                }
            }

            $hour_start->addMinutes(30);
        }

        $rowsMap = [];
        foreach ($hoursMap as $keyHour => $itemDay) {
            if (array_filter($itemDay)) {
                $valueDay = array_values($itemDay);
                array_unshift($valueDay, [$keyHour, '#e0e0e0']);
                $rowsMap[] = $valueDay;
            }
        }

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'studyProgram' => $result->studyProgram,
            'rol' => $result->rol,
            'person' => $result->person,
            'period' => $result->period,
            'columns' => $columns,
            'rows' => $rowsMap,
        ];

        $template = null;
        $view = 'exports/schedule';

        return [$data, $template, $view];
    }

    public static function exportAbsences(Request $request, object $info)
    {
        [$result, $classroom, $date] = AssistanceRepository::absencesPerDay($request);

        $title = 'ASISTENCIAS DEL ' . $date;

        $classroomColumns = [
            'study_program' => 'PROGRAMA DE ESTUDIOS',
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'study_program' => $classroom->studyPlanDetail->studyPlan->studyProgram->name,
            'course' => $classroom->studyPlanDetail->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'attended' => 'P',
            'absence' => 'F',
            'late' => 'T',
            'reason' => 'MOTIVO',
        ];
        $columnsAligned = [
            'id',
            'attended',
            'absence',
            'late',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = AbsencesTemplate::class;
        $view = 'exports/absences';

        return [$data, $template, $view];
    }

    public static function exportCapacitationAbsences(Request $request, object $info)
    {
        [$result, $training, $date] = TrainingAssistanceRepository::absencesPerDay($request);

        $title = 'ASISTENCIAS DEL ' . $date;

        $classroomColumns = [
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'attended' => 'P',
            'absence' => 'F',
            'late' => 'T',
            'reason' => 'MOTIVO',
        ];
        $columnsAligned = [
            'id',
            'attended',
            'absence',
            'late',
        ];
        $rows = $result->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
        ];

        $template = AbsencesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportAttendanceConsolidated(Request $request, object $info)
    {
        [$result, $classroom, $participants] = AssistanceRepository::consolidated($request);

        $title = 'CONSOLIDADO DE ASISTENCIAS';

        $classroomColumns = [
            'study_program' => 'PROGRAMA DE ESTUDIOS',
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'study_program' => $classroom->studyPlanDetail->studyPlan->studyProgram->name,
            'course' => $classroom->studyPlanDetail->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'assistance' => 'ASISTENCIA',
        ];
        $columnsAligned = [
            'id',
            'assistance',
        ];
        $rows = $result->groupBy('date')->toArray();
        $participants = $participants->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
            'participants' => $participants,
        ];

        $template = AttendanceConsolidatedTemplate::class;
        $view = 'exports/attendance-consolidated';

        return [$data, $template, $view];
    }

    public static function exportCapacitationAttendanceConsolidated(Request $request, object $info)
    {
        [$result, $training, $participants] = TrainingAssistanceRepository::consolidated($request);

        $title = 'CONSOLIDADO DE ASISTENCIAS';

        $classroomColumns = [
            'course' => 'UNIDAD DIDÁCTICA',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'person' => 'APELLIDOS Y NOMBRES',
            'assistance' => 'ASISTENCIA',
        ];
        $columnsAligned = [
            'id',
            'assistance',
        ];
        $rows = $result->groupBy('date')->toArray();
        $participants = $participants->toArray();

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $rows,
            'participants' => $participants,
        ];

        $template = AttendanceConsolidatedTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportFinalNotes(Request $request, object $info)
    {
        $result = EvaluationRepository::finalNotes($request);

        $title = 'Plantilla';

        $columns = [
            'id' => 'NRO',
            'document_number' => 'CÓDIGO DE ALUMNO',
            'names' => 'ALUMNO',
            'score' => 'NOTA',
        ];
        $columnsAligned = [
            'id',
            'document_number',
            'score',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = FinalNotesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportPaymentConcepts(Request $request, object $info)
    {
        $concepts = PaymentConcept::with('denomination')
            ->where('deleted_at', null)
            ->get()
            ->map(function ($concept) {
                $concept['denomination_name'] = $concept->denomination->name;
                $concept['active'] = $concept->is_active ? 'ACTIVO' : 'INACTIVO';
                $concept['can_quotas'] = $concept->can_be_paid_in_quotas ? 'SI' : 'NO';
                $concept['include_enrollment'] = $concept->include_in_enrollment ? 'SI' : 'NO';
                return $concept;
            });

        $title = 'LISTADO DE CONCEPTOS DE PAGO';

        $columns = [
            'id' => '#',
            'code' => 'CÓDIGO',
            'name' => 'NOMBRE',
            'denomination_name' => 'DENOMINACIÓN',
            'gross_amount' => 'MONTO BRUTO',
            'igv_amount' => 'MONTO IGV',
            'net_amount' => 'MONTO NETO',
            'max_quotas' => 'CUOTAS MÁXIMAS',
            'active' => 'ESTADO',
            'can_quotas' => 'PUEDE PAGARSE EN CUOTAS',
            'include_enrollment' => 'INCLUIR EN MATRÍCULA',
        ];
        $columnsAligned = [
            'id',
            'gross_amount',
            'igv_amount',
            'net_amount',
            'is_active',
            'max_quotas',
            'can_quotas',
            'include_enrollment',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $concepts,
        ];

        $template = PaymentConceptsTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportContentList(Request $request, object $info)
    {
        [$result, $classroom] = ContentRepository::report($request);

        $title = 'LISTADO DE CONTENIDO';

        $classroomColumns = [
            'teacher' => 'DOCENTE',
            'course' => 'CURSO',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $classroomRows = [
            'teacher' => $classroom->teacher->person->name ?? '-',
            'course' => $classroom->studyPlanDetail->course->name,
            'period' => $classroom->period->name,
        ];

        $columns = [
            'id' => '#',
            'content_group_title' => 'GRUPO DE CONTENIDO',
            'title' => 'NOMBRE',
            'type' => 'TIPO',
            'evaluation_group_title' => 'GRUPO DE EVALUACIÓN',
            'date' => 'FECHA DE PUBLICACIÓN',
            'date_limit' => 'FECHA DE ENTREGA',
            'status' => 'ESTADO',
        ];
        $columnsAligned = [
            'id',
            'type',
            'date',
            'date_limit',
            'status',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $classroomRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = ContentListTemplate::class;
        $view = 'exports/content-list';

        return [$data, $template, $view];
    }

    public static function exportCapacitationFinalNotes(Request $request, object $info)
    {
        $result = TrainingEvaluationRepository::finalNotes($request);

        $title = 'Plantilla';

        $columns = [
            'id' => 'NRO',
            'document_number' => 'CÓDIGO DE ALUMNO',
            'names' => 'ALUMNO',
            'score' => 'NOTA',
        ];
        $columnsAligned = [
            'id',
            'document_number',
            'score',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'date' => $info->date,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = FinalNotesTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }

    public static function exportCapacitationContentList(Request $request, object $info)
    {
        [$result, $training] = TrainingContentRepository::report($request);

        $title = 'LISTADO DE CONTENIDO';

        $classroomColumns = [
            'teacher' => 'DOCENTE',
            'course' => 'CURSO',
            'period' => 'PERIODO ACADÉMICO',
        ];
        $trainingRows = [
            'teacher' => $training->teacher->person->name ?? '-',
            'course' => $training->name,
            'period' => $training->period->name,
        ];

        $columns = [
            'id' => '#',
            'content_group_title' => 'GRUPO DE CONTENIDO',
            'title' => 'NOMBRE',
            'type' => 'TIPO',
            'evaluation_group_title' => 'GRUPO DE EVALUACIÓN',
            'date' => 'FECHA DE PUBLICACIÓN',
            'date_limit' => 'FECHA DE ENTREGA',
            'status' => 'ESTADO',
        ];
        $columnsAligned = [
            'id',
            'type',
            'date',
            'date_limit',
            'status',
        ];

        $data = (object) [
            'institutionName' => $info->institutionName,
            'institutionLogo' => $info->institutionLogo,
            'applicationName' => $info->applicationName,
            'title' => $title,
            'classroomColumns' => $classroomColumns,
            'classroomRows' => $trainingRows,
            'columns' => $columns,
            'columnsAligned' => $columnsAligned,
            'rows' => $result,
        ];

        $template = ContentListTemplate::class;
        $view = null;

        return [$data, $template, $view];
    }
}
