<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
  use SoftDeletes;

  protected $table = 'participant';

  protected $fillable = [
      'id',
      'person_id',
      'classroom_id',
      'score',
      'is_approved',
      'is_favorite',
  ];

  protected $hidden = [
      'created_at',
      'updated_at',
      'deleted_at',
  ];

  protected $casts = [
      'score' => 'float',
      'is_approved' => 'boolean',
      'is_favorite' => 'boolean',
  ];

  public function person()
  {
      return $this->belongsTo(Person::class, 'person_id');
  }

  public function classroom()
  {
      return $this->belongsTo(Classroom::class, 'classroom_id');
  }
}
