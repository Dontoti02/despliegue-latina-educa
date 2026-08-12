<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusTemplateCompetency extends Model
{

    protected $table = 'syllabus_template_competencies';

    protected $fillable = [
        'id',
        'syllabus_template_version_id',
        'sort_order',
        'name',
        'description',
        'rich_content',
        'objective',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(SyllabusTemplateVersion::class, 'syllabus_template_version_id');
    }
}
