<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SyllabusTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'syllabus_templates';

    protected $fillable = [
        'id',
        'code',
        'title',
        'description',
        'file',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function didacticUnits(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'didactic_unit_syllabus_template',
            'syllabus_template_id',
            'didactic_unit_id'
        );
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SyllabusTemplateVersion::class, 'syllabus_template_id');
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(SyllabusTemplateVersion::class, 'syllabus_template_id')
            ->where('is_published', true)
            ->latest('version_number');
    }
}
