<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use SoftDeletes;

    protected $table = 'module';

    protected $fillable = [
        'id',
        'study_program_id',
        'type_id',
        'name',
        'year',
        'order',
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

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function type()
    {
        return $this->belongsTo(ModuleType::class, 'type_id');
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'module_id');
    }
}
