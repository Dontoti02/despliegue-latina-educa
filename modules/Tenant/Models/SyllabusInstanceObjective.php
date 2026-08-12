<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusInstanceObjective extends Model
{

    protected $table = 'syllabus_instance_objectives';

    protected $fillable = [
        'id',
        'syllabus_instance_competency_id',
        'description',
        'is_completed',
        'completed_at',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(SyllabusInstanceCompetency::class, 'syllabus_instance_competency_id');
    }
}
