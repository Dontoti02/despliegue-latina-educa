<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusTemplateVersion extends Model
{

    protected $table = 'syllabus_template_versions';

    protected $fillable = [
        'id',
        'syllabus_template_id',
        'version_number',
        'is_published',
        'created_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'version_number' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(SyllabusTemplate::class, 'syllabus_template_id');
    }

    public function competencies(): HasMany
    {
        return $this->hasMany(SyllabusTemplateCompetency::class, 'syllabus_template_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
