<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use SoftDeletes;

    protected $table = 'enrollment';

    protected $fillable = [
        'id',
        'student_plan_id',
        'type_id',
        'period_id',
        'cycle_id',
        'shift_id',
        'section_id',
        'is_approved',
        'registration_date',

        'observations',
        'is_full_payment',

        'scale_id',
        'scale_authorization_document_type',
        'scale_authorization_document_number',
        'scale_authorization_full_names',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_full_payment' => 'boolean',
    ];

    public function studentPlan()
    {
        return $this->belongsTo(StudentPlan::class, 'student_plan_id');
    }

    public function type()
    {
        return $this->belongsTo(EnrollmentType::class, 'type_id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function cycle()
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function scale()
    {
        return $this->belongsTo(Scale::class, 'scale_id');
    }
}
