<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Tenant\Models\File;
use Modules\Tenant\Models\SyllabusInstanceCompetency;

class SyllabusInstance extends Model
{

    protected $table = 'syllabus_instances';

    protected $fillable = [
        'id',
        'classroom_id',
        'syllabus_template_version_id',
        'title',
        'description',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function competencies(): HasMany
    {
        return $this->hasMany(SyllabusInstanceCompetency::class, 'syllabus_instance_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
