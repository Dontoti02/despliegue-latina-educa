<?php

namespace Modules\Tenant\Packages\SyllabusManager\Repositories;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Models\Classroom;
use Modules\Tenant\Models\SyllabusInstance;
use Modules\Tenant\Models\SyllabusInstanceCompetency;
use Modules\Tenant\Models\SyllabusCompetencyHistory;
use Modules\Tenant\Models\SyllabusTemplate;
use Modules\Tenant\Packages\Classroom\Helpers\ClassroomHelper;
use Modules\Tenant\Packages\File\Repositories\FileRepository;
use Modules\Tenant\Services\FileTenantService;

class SyllabusInstanceRepository
{
    public function findByClassroomId(int $classroomId): SyllabusInstance
    {
        return SyllabusInstance::with(['competencies', 'files'])
            ->where('classroom_id', (string) $classroomId)
            ->firstOrFail();
    }

    public function findById(string $id): SyllabusInstance
    {
        return SyllabusInstance::with(['competencies', 'files'])->findOrFail($id);
    }
    public function recordCompetencyStatusChange(string $competencyId, string $newStatus, ?int $changedBy = null, ?string $comment = null): array
    {
        $competency = SyllabusInstanceCompetency::find($competencyId);
        if (!$competency) {
            throw new \Exception('Competency not found');
        }

        $syllabus = $competency->syllabusInstance;

        $total = $syllabus->competencies()->count();
        $completedCount = $syllabus->competencies()->where('status', 'completed')->count();
        if ($total > 0 && $completedCount === $total) {
            throw new \Exception('Syllabus already completed');
        }

        $previous = $competency->status;
        if ($previous === $newStatus) {
        }

        $now = now();
        $data = [];
        $data['status'] = $newStatus;
        $data['status_changed_at'] = $now;
        if ($newStatus === 'in_progress' && !$competency->started_at) {
            $data['started_at'] = $now;
        }
        if ($newStatus === 'completed' && !$competency->completed_at) {
            $data['completed_at'] = $now;
        }

        $competency->fill($data);
        $competency->save();

        $history = SyllabusCompetencyHistory::create([
            'syllabus_instance_competency_id' => $competency->id,
            'previous_status' => $previous,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'comment' => $comment,
            'created_at' => $now,
        ]);

        // recalcular porcentaje
        $completedAfter = $syllabus->competencies()->where('status', 'completed')->count();
        $percent = $total > 0 ? intval(round($completedAfter / $total * 100)) : 0;

        return [
            'history' => $history->toArray(),
            'percent' => $percent,
        ];
    }

    public function getTimelineForSyllabus(string $syllabusId): array
    {
        $syllabus = SyllabusInstance::with(['competencies', 'competencies.objectives'])->find($syllabusId);
        if (!$syllabus) {
            throw new \Exception('Syllabus not found');
        }

        $histories = SyllabusCompetencyHistory::whereIn('syllabus_instance_competency_id', $syllabus->competencies->pluck('id')->toArray())
            ->orderBy('created_at', 'asc')
            ->get();

        // Map events grouped by competency
        $eventsByCompetency = [];
        foreach ($histories as $h) {
            $eventsByCompetency[$h->syllabus_instance_competency_id][] = $h;
        }

        $total = $syllabus->competencies->count();
        $completed = 0;

        // Build global ordered events for timeline with percent after each event
        $globalEvents = [];
        foreach ($histories as $h) {
            if ($h->new_status === 'completed') {
                $completed++;
            }
            $percent = $total > 0 ? intval(round($completed / $total * 100)) : 0;
            $globalEvents[] = [
                'timestamp' => $h->created_at,
                'syllabus_instance_competency_id' => $h->syllabus_instance_competency_id,
                'previous_status' => $h->previous_status,
                'new_status' => $h->new_status,
                'changed_by' => $h->changed_by,
                'comment' => $h->comment,
                'percent' => $percent,
            ];
        }

        // Build competency payload
        $competencies = $syllabus->competencies->map(function ($c) use ($eventsByCompetency) {
            return [
                'id' => $c->id,
                'title' => $c->title ?? $c->name ?? null,
                'order' => $c->sort_order,
                'description' => $c->description ?? null,
                'objective' => $c->objective,
                'current_status' => $c->status,
                'events' => isset($eventsByCompetency[$c->id]) ? array_map(fn($e) => [
                    'timestamp' => $e->created_at,
                    'previous_status' => $e->previous_status,
                    'new_status' => $e->new_status,
                    'changed_by' => $e->changed_by,
                    'comment' => $e->comment,
                ], $eventsByCompetency[$c->id]) : [],
            ];
        })->toArray();

        return [
            'syllabus' => $syllabus->toArray(),
            'competencies' => $competencies,
            'events' => $globalEvents,
            'total_percent' => $total > 0 ? intval(round($syllabus->competencies()->where('status', 'completed')->count() / $total * 100)) : 0,
        ];
    }

    public function create(array $data): SyllabusInstance
    {
        if (SyllabusInstance::where('classroom_id',$data['classroom_id'])->exists()) {
            throw new Exception('Ya existe un syllabus para esta aula.');
        }

        ClassroomHelper::validateAccess($data['classroom_id'], 'teacher');

        $syllabus = SyllabusInstance::create([
            'classroom_id' => $data['classroom_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->createCompetencies($syllabus, $data['competencies']);

        if (!empty($data['file'])) {
          $this->saveFile($syllabus, $data['file'],(int) $data['classroom_id']);
        }

        // return $syllabus;
        return $this->refreshInstance($syllabus);
    }

    public function update(SyllabusInstance $syllabus, array $data): SyllabusInstance
    {
        ClassroomHelper::validateAccess((int) $syllabus->classroom_id, 'teacher');

        $syllabus->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);

        $this->updateCompetencies($syllabus, $data['competencies']);

        if (!empty($data['file'])) {
            $this->replaceFile($syllabus, $data['file'], $syllabus->classroom_id);
        }

        return $this->refreshInstance($syllabus);
    }

    public function listTemplatesForClassroom(int $classroomId): array
    {
        $classroom = Classroom::findOrFail($classroomId);

        $query = SyllabusTemplate::with(['activeVersion.competencies'])
            ->where('is_active', true)
            ->orderByDesc('created_at');

        $courseId = $classroom->studyPlanDetail?->course_id;
        if (!empty($courseId)) {
            $query->whereHas('didacticUnits', function ($q) use ($courseId) {
                $q->where('course.id', $courseId);
            });
        }

        $templates = $query->get();

        return $templates->map(function (SyllabusTemplate $template) {
            $competencies = [];
            if ($template->activeVersion) {
                $competencies = $template->activeVersion->competencies->map(function ($competency) {
                    return [
                        'id' => $competency->id,
                        'name' => $competency->name,
                        'description' => $competency->description,
                        'objective' => $competency->objective,
                        'order' => $competency->sort_order,
                    ];
                })->values()->all();
            }

            return [
                'id' => $template->id,
                'code' => $template->code,
                'title' => $template->title,
                'description' => $template->description,
                'competencies' => $competencies,
            ];
        })->all();
    }

    protected function createCompetencies(SyllabusInstance $syllabus, array $competencies): void
    {
        foreach ($this->normalizeCompetencies($competencies) as $item) {
          try {
            Log::alert("item(competence):". json_encode($item));
            $syllabus->competencies()->create($item);
            
          } catch(\Exception $e) {
            Log::error("error competence:" . $e->getMessage());
          }
        }
    }

    protected function updateCompetencies(SyllabusInstance $syllabus, array $competencies): void
    {
        $incoming = collect($competencies)->keyBy(fn ($item) => $item['id'] ?? null);
        $existing = $syllabus->competencies()->get()->keyBy('id');

        foreach ($incoming as $incomingId => $incomingCompetency) {
            if (empty($incomingId) || !$existing->has($incomingId)) {
                $item = $this->normalizeCompetencyUpdate($incomingCompetency);
                $item['id'] = $incomingId ?: null;
                $syllabus->competencies()->create($item);
                continue;
            }

            $competency = $existing->get($incomingId);
            $competency->update([
                'sort_order' => $incomingCompetency['order'] ?? $competency->sort_order,
                'name' => $incomingCompetency['name'] ?? $competency->name,
                'description' => $incomingCompetency['description'] ?? $competency->description,
                'objective' => $incomingCompetency['objective'] ?? $competency->objective,
            ]);
        }

        $toRemove = $existing->filter(function ($competency) use ($incoming) {
          return !$incoming->has($competency->id) && $competency->status === 'pending';
        });

        foreach ($toRemove as $competency) {
            $competency->delete();
        }
    }

    protected function normalizeCompetencyUpdate(array $competency): array
    {
        return [
            'sort_order' => $competency['order'] ?? 1,
            'name' => $competency['name'],
            'description' => $competency['description'] ?? null,
            'rich_content' => $competency['rich_content'] ?? null,
            'objective' => $competency['objective'] ?? null,
            'status' => $competency['status'] ?? 'pending',
            'status_changed_at' => Carbon::now(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    protected function saveFile(SyllabusInstance $syllabus, $file, int $classroomId): void
    {
        $path = 'classroom_' . $classroomId . '/syllabus';
        $item = FileTenantService::save($syllabus, $file, $path);
        Log::info("file". json_encode($item));
    }

    protected function replaceFile(SyllabusInstance $syllabus, $file, string $classroomId): void
    {
        $files = $syllabus->files;
        if ($files->count() > 0) {
            FileTenantService::delete($files->all());
        }

        $this->saveFile($syllabus, $file, (int) $classroomId);
    }

    protected function refreshInstance(SyllabusInstance $syllabus): SyllabusInstance
    {
        return $syllabus->load(['competencies', 'files']);
    }

    protected function normalizeCompetencies(array $competencies): array
    {
        return array_values(array_map(function ($competency) {
            $status = $this->normalizeStatus($competency['status'] ?? 'pending');
            $now = Carbon::now();

            return [
                'id' => $competency['id'] ?? null,
                'sort_order' => $competency['order'] ?? 1,
                'name' => $competency['name'],
                'description' => $competency['description'] ?? null,
                'rich_content' => $competency['rich_content'] ?? null,
                'objective' => $competency['objective'] ?? null,
                'status' => $status,
                'status_changed_at' => $now,
                'started_at' => $status === 'in_progress' ? $now : null,
                'completed_at' => $status === 'completed' ? $now : null,
            ];
        }, array_filter($competencies, function ($competency) {
            return is_array($competency) && !empty($competency['name']);
        })));
    }

    protected function normalizeStatus(string $status): string
    {
        return $status;
    }

    public function transformInstance(SyllabusInstance $syllabus): array
    {
        $syllabus->load(['competencies', 'files']);

        $competencies = $syllabus->competencies->sortBy('sort_order')->map(function (SyllabusInstanceCompetency $competency) {
            return [
                'id' => $competency->id,
                'name' => $competency->name,
                'description' => $competency->description,
                'objective' => $competency->objective,
                'order' => $competency->sort_order,
                'status' => $this->normalizeStatusForResponse($competency->status),
            ];
        })->values()->all();

        $file = null;
        $firstFile = FileRepository::listByModel($syllabus)->first();
        if ($firstFile) {
            $file = [
                'id' => $firstFile->id,
                'url' => $firstFile->url,
                'uuid' => $firstFile->uuid,
                'metadata' => $firstFile->metadata,
            ];
        }

        return [
            'id' => $syllabus->id,
            'title' => $syllabus->title,
            'description' => $syllabus->description,
            'classroom_id' => (int) $syllabus->classroom_id,
            'competencies' => $competencies,
            'file' => $file,
        ];
    }

    protected function normalizeStatusForResponse(string $status): string
    {
        return $status === 'completed' ? 'completed' : $status;
    }
}
