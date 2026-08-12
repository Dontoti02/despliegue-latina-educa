<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementDetails extends Model
{
    use SoftDeletes;

    protected $table = 'treasury_movement_detail';

    protected $fillable = [
        'id',
        'treasury_movement_id',
        'treasury_payment_concept_id',
        'person_registration_payment_id',
        'person_created_schedule_by',
        'amount',
        'is_paid',
        'due_date',
        'emission_date',
        'payment_date',
        'movement_voucher'
    ];

    protected $casts = [
        'amount' => 'float',
        'is_paid' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    function movement()
    {
        return $this->belongsTo(Movement::class, 'treasury_movement_id');
    }

    function paymentConcept()
    {
        return $this->belongsTo(PaymentConcept::class, 'treasury_payment_concept_id');
    }

    function personRegistrationPayment()
    {
        return $this->belongsTo(Person::class, 'person_registration_payment_id');
    }

    function personCreatedScheduleBy()
    {
        return $this->belongsTo(Person::class, 'person_created_schedule_by');
    }
}
