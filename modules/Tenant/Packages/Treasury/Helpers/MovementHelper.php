<?php

namespace Modules\Tenant\Packages\Treasury\Helpers;

use Modules\Tenant\Models\Movement;
use Modules\Tenant\Models\MovementType;

class MovementHelper
{
  public static function hasMovementsPendingPayment($paymentConceptId)
  {
    Movement::where('treasury_payment_concept_id', $paymentConceptId)
      ->where('is_paid', true)
      ->where('treasury_movement_type_id', MovementType::INCOME)
      ->exists();
  }
}
