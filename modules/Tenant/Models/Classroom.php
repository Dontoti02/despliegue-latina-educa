<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classroom extends Model
{
    use SoftDeletes;

    protected $table = 'classroom';

    protected $fillable = [
        'id',
        'period_id',
        'study_plan_detail_id',
        'shift_id',
        'section_id',
        'teacher_id',
        'is_closed',
        'avatar',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function studyPlanDetail()
    {
        return $this->belongsTo(StudyPlanDetail::class, 'study_plan_detail_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'classroom_id');
    }

    public function assistances()
    {
        return $this->hasMany(Assistance::class, 'classroom_id');
    }

    public function contentGroups()
    {
        return $this->hasMany(ContentGroup::class, 'classroom_id');
    }

    public function evaluationGroups()
    {
        return $this->hasMany(EvaluationGroup::class, 'classroom_id');
    }

    public function publications()
    {
        return $this->hasMany(Publication::class, 'classroom_id');
    }
}
