<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Models\SyllabusInstance;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Models\SyllabusInstanceObjective;

class SyllabusInstanceCompetency extends Model
{

    protected $table = 'syllabus_instance_competencies';

    protected $fillable = [
        'id',
        'syllabus_instance_id',
        'sort_order',
        'name',
        'description',
        'rich_content',
        'objective',
        'status',
        'status_changed_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function syllabusInstance(): BelongsTo
    {
        return $this->belongsTo(SyllabusInstance::class, 'syllabus_instance_id');
    }

    public function objectives(): HasMany
    {
        return $this->hasMany(SyllabusInstanceObjective::class, 'syllabus_instance_competency_id');
    }
}
