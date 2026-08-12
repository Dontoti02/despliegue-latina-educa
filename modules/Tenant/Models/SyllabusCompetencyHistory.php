<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusCompetencyHistory extends Model
{

    protected $table = 'syllabus_competency_histories';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'syllabus_instance_competency_id',
        'previous_status',
        'new_status',
        'changed_by',
        'comment',
        'created_at',
    ];

    public function competency(): BelongsTo
    {
        return $this->belongsTo(SyllabusInstanceCompetency::class, 'syllabus_instance_competency_id');
    }
}
