<?php

namespace Modules\Tenant\Packages\SyllabusManager\Repositories;

use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Tenant\Models\SyllabusTemplate;
use Modules\Tenant\Models\SyllabusTemplateCompetency;
use Modules\Tenant\Models\SyllabusTemplateVersion;

class SyllabusTemplateRepository
{
    public function list(?string $search = null, ?int $didacticUnitId = null, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        $query = SyllabusTemplate::query()
            ->with(['activeVersion', 'didacticUnits'])
            ->when(!empty($search), function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('code', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->when(!empty($didacticUnitId), function ($query) use ($didacticUnitId) {
                $query->whereHas('didacticUnits', function ($relation) use ($didacticUnitId) {
                    $relation->where('course.id', $didacticUnitId);
                });
            })
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (SyllabusTemplate $template) {
            $template->competency_count = $template->activeVersion?->competencies()->count() ?? 0;
            return $template;
        });

        return $paginator;
    }

    public function create(array $data): SyllabusTemplate
    {
      $code = $this->generateCode();

      $template = SyllabusTemplate::create([
          'code' => $code,
          'title' => $data['title'],
          'description' => $data['description'] ?? null,
          'is_active' => true,
      ]);

      if (!empty($data['didactic_units'])) {
          $template->didacticUnits()->sync($this->normalizeDidacticUnits($data['didactic_units']));
      }

      $version = SyllabusTemplateVersion::create([
          'syllabus_template_id' => $template->id,
          'version_number' => 1,
          'is_published' => true,
      ]);

      foreach ($this->normalizeCompetencies($data['competencies'] ?? []) as $index => $competencyData) {
          SyllabusTemplateCompetency::create([
              'syllabus_template_version_id' => $version->id,
              'sort_order' => $competencyData['sort_order'] ?? ($index + 1),
              'name' => $competencyData['name'],
              'description' => $competencyData['description'] ?? null,
              'rich_content' => $competencyData['rich_content'] ?? null,
              'objective' => $competencyData['objective'] ?? null,
          ]);
      }

      return $template;
    }

    public function update(SyllabusTemplate $template, array $data): SyllabusTemplate
    {
      $template->update([
          'title' => $data['title'],
          'description' => $data['description'] ?? null,
          'is_active' => $data['is_active'] ?? $template->is_active,
      ]);

      if (array_key_exists('didactic_units', $data)) {
          $template->didacticUnits()->sync($this->normalizeDidacticUnits($data['didactic_units'] ?? []));
      }

      $activeVersion = $template->activeVersion()->first();

      if (!$activeVersion) {
          $activeVersion = SyllabusTemplateVersion::create([
              'syllabus_template_id' => $template->id,
              'version_number' => 1,
              'is_published' => true,
          ]);
      }

      $existingCompetencies = $activeVersion->competencies()->pluck('id');
      $incomingCompetencies = $this->normalizeCompetencies($data['competencies'] ?? []);

      if (!empty($incomingCompetencies)) {
          $activeVersion->competencies()->delete();
          foreach ($incomingCompetencies as $index => $competencyData) {
              $activeVersion->competencies()->create([
                  'sort_order' => $competencyData['sort_order'] ?? ($index + 1),
                  'name' => $competencyData['name'],
                  'description' => $competencyData['description'] ?? null,
                  'rich_content' => $competencyData['rich_content'] ?? null,
                  'objective' => $competencyData['objective'] ?? null,
              ]);
          }
      }

      return $template;
    }

    public function findOrFail(string $id): SyllabusTemplate
    {
        return SyllabusTemplate::with(['didacticUnits', 'activeVersion.competencies'])->findOrFail($id);
    }

    protected function normalizeDidacticUnits(array $didacticUnits): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $didacticUnits))));
    }

    protected function normalizeCompetencies(array $competencies): array
    {
        return array_values(array_filter($competencies, function ($competency) {
            return is_array($competency) && !empty($competency['name']);
        }));
    }

    protected function generateCode(): string
    {
        do {
            $random = strtoupper(Str::random(25));
            $code = implode('-', str_split($random, 5));
        } while (SyllabusTemplate::where('code', $code)->exists());

        return $code;
    }
}
