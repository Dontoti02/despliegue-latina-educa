<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudyPlanType extends Model
{
    use SoftDeletes;

    protected $table = 'study_plan_type';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlan::class, 'type_id');
    }
}
