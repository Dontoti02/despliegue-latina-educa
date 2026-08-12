<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyPlan extends Model
{
    use SoftDeletes;

    protected $table = 'study_plan';

    protected $fillable = [
        'id',
        'study_program_id',
        'type_id',
        'name',
        'year',
        'is_active',
        'score_min_to_pass',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'score_min_to_pass' => 'float',
    ];

    public function studyProgram()
    {
        return $this->belongsTo(StudyProgram::class, 'study_program_id');
    }

    public function type()
    {
        return $this->belongsTo(StudyPlanType::class, 'type_id');
    }

    public function studentPlans()
    {
        return $this->hasMany(StudentPlan::class, 'study_plan_id');
    }

    public function studyPlanDetails()
    {
        return $this->hasMany(StudyPlanDetail::class, 'study_plan_id');
    }
}
