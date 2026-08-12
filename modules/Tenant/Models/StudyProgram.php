<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyProgram extends Model
{
    use SoftDeletes;

    protected $table = 'study_program';

    protected $fillable = [
        'id',
        'productive_family_id',
        'name',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function productiveFamily(): BelongsTo
    {
        return $this->belongsTo(ProductiveFamily::class, 'productive_family_id');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class, 'study_program_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class, 'study_program_id');
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'study_program_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'study_program_id');
    }
}
