<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingCategory extends Model
{
  protected $table = 'training_category';

  public $timestamps = false;

  protected $fillable = [
    'id',
    'name',
  ];
}
