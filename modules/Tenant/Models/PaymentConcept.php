<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentConcept extends Model
{
    use SoftDeletes;

    protected $table = 'treasury_payment_concept';

    protected $fillable = [
        'id',
        'code',
        'name',
        'treasury_denomination_id',
        'gross_amount',
        'net_amount',
        'igv_amount',
        'max_quotas',
        'is_active',
        'can_be_paid_in_quotas',
        'include_in_enrollment'
    ];

    protected $casts = [
        'gross_amount' => 'float',
        'net_amount' => 'float',
        'igv_amount' => 'float',
        'is_active' => 'boolean',
        'can_be_paid_in_quotas' => 'boolean',
        'include_in_enrollment' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function denomination()
    {
        return $this->belongsTo(Denomination::class, 'treasury_denomination_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class, 'treasury_payment_concept_id');
    }

    public function movementDetails()
    {
        return $this->hasMany(MovementDetails::class, 'treasury_payment_concept_id');
    }

    public static function generateCode(): string
    {
        $last = self::orderBy('id', 'desc')->first();

        if ($last) {
            $lastCode = $last->code;
            $lastCode = explode('-', $lastCode);
            $lastCode = $lastCode[1];
            $lastCode = (int)$lastCode;
            $lastCode++;
            $lastCode = str_pad($lastCode, 4, '0', STR_PAD_LEFT);

            return 'PC-' . $lastCode;
        }

        return 'PC-0001';
    }
}
