<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movement extends Model
{
    use SoftDeletes;

    protected $table = 'treasury_movement';

    protected $fillable = [
        'id',
        'code',
        'treasury_movement_type_id',
        'treasury_payment_concept_id',
        'period_id',
        'person_id',
        'person_registration_id',
        'amount',
        'initial_amount',
        'amount_to_divide',
        'quotas',
        'is_paid',
        'remaining_amount',
        'due_date',
        'payment_date',
        'is_exonerated',
        'exoneration_reason',
        'refund_movement_id',
        'discounts'
    ];

    protected $casts = [
        'amount' => 'float',
        'initial_amount' => 'float',
        'amount_to_divide' => 'float',
        'remaining_amount' => 'float',
        'is_paid' => 'boolean',
        'is_exonerated' => 'boolean',
        'discounts' => 'float'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function movementType()
    {
        return $this->belongsTo(MovementType::class, 'treasury_movement_type_id');
    }

    public function paymentConcept()
    {
        return $this->belongsTo(PaymentConcept::class, 'treasury_payment_concept_id');
    }

    public function period()
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function personRegistration()
    {
        return $this->belongsTo(Person::class, 'person_registration_id');
    }

    public function movementDetails()
    {
        return $this->hasMany(MovementDetails::class, 'treasury_movement_id');
    }
}
