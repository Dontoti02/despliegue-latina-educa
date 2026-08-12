<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $table = 'course';

    protected $fillable = [
        'id',
        'study_program_id',
        'module_id',
        'type_id',
        'code',
        'name',
        'year',
        'credits',
        'hours',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credits' => 'integer'
    ];

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function module()
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function type()
    {
        return $this->belongsTo(CourseType::class, 'type_id');
    }

    public function studyPlanDetails()
    {
        return $this->hasMany(StudyPlanDetail::class, 'course_id');
    }
}
