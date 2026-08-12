<?php

namespace Modules\Tenant\Packages\SyllabusManager\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Helpers\SystemConfigurationHelper as AdminSystemConfigurationHelper;
use Modules\Shared\Utils\Generate;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\Course;
use Modules\Tenant\Models\Period;
use Modules\Tenant\Models\StudyProgram;
use Modules\Tenant\Models\SyllabusInstance;
use Modules\Tenant\Models\SyllabusInstanceCompetency;
use Modules\Tenant\Packages\SyllabusManager\Helpers\SyllabusProgressHelper;
use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;

class SyllabusProgressRepository
{
    /**
     * Unidades didácticas activas, paginadas, con el último sílabo asociado
     * a cualquiera de las clases (classroom) de la unidad.
     */
    public static function list(Request $request)
    {
        SyllabusProgressHelper::validateListRequest($request);

        $page = (int) $request->input('page');
        $size = (int) $request->input('size');
        $periodId = $request->input('period_id');

        $courses = self::baseQuery($request)->paginate($size, ['*'], 'page', $page);

        $items = self::attachSyllabus(collect($courses->items()), $periodId);

        $result = [
            'page' => $page,
            'size' => $size,
            'total' => $courses->total(),
            'items' => $items,
        ];

        return $result;
    }

    /**
     * Informe de progreso curricular en PDF.
     */
    public static function download(string $type, Request $request)
    {
        if ($type !== 'pdf') {
            throw new Exception('El tipo de archivo no es válido.');
        }

        SyllabusProgressHelper::validateReportRequest($request);

        $periodId = $request->input('period_id');

        $units = self::attachSyllabus(self::baseQuery($request)->get(), $periodId);

        if (empty($units)) {
            throw new Exception('No se encontraron unidades didácticas para generar el informe.');
        }

        $title = 'INFORME DE PROGRESO CURRICULAR';

        $institutionName = SystemConfigurationHelper::getInstitutionName();
        $institutionName = strtoupper((string) $institutionName);

        $institutionLogo = SystemConfigurationHelper::getInstitutionLogo();

        $applicationName = AdminSystemConfigurationHelper::getName();
        $applicationName = strtoupper((string) $applicationName);

        $date = Carbon::now();
        $date = $date->isoFormat('dddd, D [de] MMMM [del] YYYY');

        $data = (object) [
            'institutionName' => $institutionName,
            'institutionLogo' => $institutionLogo,
            'applicationName' => $applicationName,
            'title' => $title,
            'date' => $date,
            'filters' => self::reportFilters($request),
            'summary' => self::reportSummary($units),
            'units' => $units,
        ];

        $view = 'exports/syllabus-progress';

        return Generate::generatePdf($view, $data);
    }

    protected static function baseQuery(Request $request)
    {
        $search = $request->input('search');
        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');
        $courseIds = $request->input('course_ids');

        $query = Course::select(['id', 'study_program_id', 'code', 'name', 'year', 'credits', 'hours'])
            ->with(['studyProgram:id,name'])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $word = trim($search);
                    $query
                        ->orWhere('code', 'like', "%$word%")
                        ->orWhere('name', 'like', "%$word%")
                        ->orWhere('year', 'like', "%$word%");
                });
            })
            ->when($studyProgramId, function ($query) use ($studyProgramId) {
                $query->where('study_program_id', $studyProgramId);
            })
            ->when(!empty($courseIds), function ($query) use ($courseIds) {
                $query->whereIn('id', $courseIds);
            })
            ->when($periodId, function ($query) use ($periodId) {
                $query->whereHas('studyPlanDetails.classrooms', function ($query) use ($periodId) {
                    $query->where('period_id', $periodId);
                });
            })
            // Las unidades con sílabo configurado siempre van primero.
            ->addSelect(['has_syllabus' => self::hasSyllabusSubQuery($periodId)])
            ->orderByDesc('has_syllabus')
            ->orderBy('name', 'asc');

        return $query;
    }

    /**
     * Devuelve 1 si la unidad didáctica tiene al menos un sílabo registrado
     * en alguna de sus aulas, NULL en caso contrario.
     */
    protected static function hasSyllabusSubQuery($periodId = null)
    {
        return DB::table('study_plan_detail')
            ->selectRaw('1')
            ->join('classroom', 'classroom.study_plan_detail_id', '=', 'study_plan_detail.id')
            ->join('syllabus_instances', 'syllabus_instances.classroom_id', '=', 'classroom.id')
            ->whereColumn('study_plan_detail.course_id', 'course.id')
            ->whereNull('study_plan_detail.deleted_at')
            ->whereNull('classroom.deleted_at')
            ->when($periodId, function ($query) use ($periodId) {
                $query->where('classroom.period_id', $periodId);
            })
            ->limit(1);
    }

    /**
     * course -> study_plan_detail -> classroom -> syllabus_instance (el más reciente).
     */
    protected static function attachSyllabus(Collection $courses, $periodId = null): array
    {
        $courseIds = $courses->pluck('id')->all();

        if (empty($courseIds)) {
            return [];
        }

        $classrooms = Classroom::select(['id', 'period_id', 'study_plan_detail_id', 'section_id', 'shift_id', 'teacher_id'])
            ->with([
                'period:id,name',
                'section:id,name',
                'shift:id,name',
                'teacher.person:id,names',
                'studyPlanDetail:id,course_id',
            ])
            ->whereHas('studyPlanDetail', function ($query) use ($courseIds) {
                $query->whereIn('course_id', $courseIds);
            })
            ->when($periodId, function ($query) use ($periodId) {
                $query->where('period_id', $periodId);
            })
            ->get();

        $classroomsByCourse = $classrooms->groupBy(function (Classroom $classroom) {
            return $classroom->studyPlanDetail?->course_id;
        });

        // classroom_id se almacena como string en syllabus_instances.
        $classroomIds = $classrooms->pluck('id')->map(fn ($id) => (string) $id)->all();

        $instancesByClassroom = SyllabusInstance::with(['competencies'])
            ->whereIn('classroom_id', $classroomIds)
            ->get()
            ->groupBy(fn (SyllabusInstance $instance) => (int) $instance->classroom_id);

        return $courses->map(function (Course $course) use ($classroomsByCourse, $instancesByClassroom) {
            $courseClassrooms = $classroomsByCourse->get($course->id, collect());

            $latest = null;
            $latestClassroom = null;

            foreach ($courseClassrooms as $classroom) {
                foreach ($instancesByClassroom->get($classroom->id, collect()) as $instance) {
                    if (!$latest || self::isNewer($instance, $latest)) {
                        $latest = $instance;
                        $latestClassroom = $classroom;
                    }
                }
            }

            return [
                'id' => $course->id,
                'code' => $course->code,
                'name' => $course->name,
                'year' => $course->year,
                'credits' => $course->credits,
                'hours' => $course->hours,
                'study_program' => $course->studyProgram?->name,
                'syllabus' => $latest ? self::transformSyllabus($latest, $latestClassroom) : null,
            ];
        })->values()->all();
    }

    protected static function isNewer(SyllabusInstance $candidate, SyllabusInstance $current): bool
    {
        $candidateDate = $candidate->created_at ? $candidate->created_at->timestamp : 0;
        $currentDate = $current->created_at ? $current->created_at->timestamp : 0;

        if ($candidateDate === $currentDate) {
            return (int) $candidate->id > (int) $current->id;
        }

        return $candidateDate > $currentDate;
    }

    protected static function transformSyllabus(SyllabusInstance $syllabus, ?Classroom $classroom): array
    {
        $competencies = $syllabus->competencies->sortBy('sort_order')->values();

        $total = $competencies->count();
        $completed = $competencies->where('status', 'completed')->count();
        $inProgress = $competencies->where('status', 'in_progress')->count();

        return [
            'id' => $syllabus->id,
            'name' => $syllabus->title,
            'description' => $syllabus->description,
            'classroom_id' => (int) $syllabus->classroom_id,
            'classroom' => $classroom ? [
                'id' => $classroom->id,
                'period' => $classroom->period?->name,
                'section' => $classroom->section?->name,
                'shift' => $classroom->shift?->name,
                'teacher' => $classroom->teacher?->person?->names,
            ] : null,
            'total_competencies' => $total,
            'completed_competencies' => $completed,
            'in_progress_competencies' => $inProgress,
            'pending_competencies' => $total - $completed - $inProgress,
            'total_percent' => $total > 0 ? intval(round($completed / $total * 100)) : 0,
            'created_at' => self::formatDateTime($syllabus->created_at),
            'competencies' => $competencies->map(function (SyllabusInstanceCompetency $competency) {
                return [
                    'id' => $competency->id,
                    'order' => $competency->sort_order,
                    'name' => $competency->name,
                    'description' => $competency->description,
                    'objective' => $competency->objective,
                    'status' => $competency->status,
                    'status_label' => self::statusLabel($competency->status),
                    'started_at' => self::formatDateTime($competency->started_at),
                    'completed_at' => self::formatDateTime($competency->completed_at),
                ];
            })->all(),
        ];
    }

    protected static function reportSummary(array $units): array
    {
        $withSyllabus = array_values(array_filter($units, fn ($unit) => !empty($unit['syllabus'])));

        $percents = array_map(fn ($unit) => $unit['syllabus']['total_percent'], $withSyllabus);
        $totalCompetencies = array_sum(array_map(fn ($unit) => $unit['syllabus']['total_competencies'], $withSyllabus));
        $completedCompetencies = array_sum(array_map(fn ($unit) => $unit['syllabus']['completed_competencies'], $withSyllabus));

        return [
            'total_units' => count($units),
            'units_with_syllabus' => count($withSyllabus),
            'units_without_syllabus' => count($units) - count($withSyllabus),
            'total_competencies' => $totalCompetencies,
            'completed_competencies' => $completedCompetencies,
            'average_percent' => count($percents) > 0 ? intval(round(array_sum($percents) / count($percents))) : 0,
        ];
    }

    protected static function reportFilters(Request $request): array
    {
        $periodId = $request->input('period_id');
        $studyProgramId = $request->input('study_program_id');

        $period = $periodId ? Period::select(['id', 'name'])->find($periodId) : null;
        $studyProgram = $studyProgramId ? StudyProgram::select(['id', 'name'])->find($studyProgramId) : null;

        return [
            'PERIODO' => $period?->name ?? 'Todos',
            'PROGRAMA DE ESTUDIOS' => $studyProgram?->name ?? 'Todos',
            'BÚSQUEDA' => $request->input('search') ?: '-',
        ];
    }

    protected static function formatDateTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    protected static function statusLabel(?string $status): string
    {
        return match ($status) {
            'completed' => 'Completado',
            'in_progress' => 'En progreso',
            'pending' => 'Pendiente',
            default => (string) $status,
        };
    }
}
