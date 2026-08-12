<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyPlanDetail extends Model
{
    use SoftDeletes;

    protected $table = 'study_plan_detail';

    protected $fillable = [
        'id',
        'study_plan_id',
        'cycle_id',
        'course_id',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function studyPlan()
    {
        return $this->belongsTo(StudyPlan::class, 'study_plan_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'study_plan_detail_id');
    }
}
