<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cycle extends Model
{
    use SoftDeletes;

    protected $table = 'cycle';

    protected $fillable = [
        'id',
        'name',
        'order',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function studyPlanDetails()
    {
        return $this->hasMany(StudyPlanDetail::class, 'cycle_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'cycle_id');
    }
}
