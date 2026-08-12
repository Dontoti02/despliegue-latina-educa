<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MovementType extends Model
{
  use SoftDeletes;

  const INCOME = 1;
  const EXPENSE = 2;

  protected $table = 'treasury_movement_type';

  protected $fillable = [
    'id',
    'code',
    'name',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function movements()
  {
    return $this->hasMany(Movement::class, 'treasury_movement_type_id');
  }
}
