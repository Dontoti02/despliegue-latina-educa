<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Period extends Model
{
    use SoftDeletes;

    protected $table = 'period';

    protected $fillable = [
        'id',
        'name',
        'is_current',
        'start_date',
        'end_date',
        'enrollment_start_date',
        'enrollment_end_date',
        'classroom_start_date',
        'classroom_end_date',
        'type_min_requirement_to_pass',
        'min_requirement_to_pass',
        'is_required_enrollment_payment',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'min_requirement_to_pass' => 'float',
        'is_required_enrollment_payment' => 'boolean',
    ];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class, 'period_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'period_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class, 'period_id');
    }

    public function trainings()
    {
        return $this->hasMany(Training::class, 'period_id');
    }
}
