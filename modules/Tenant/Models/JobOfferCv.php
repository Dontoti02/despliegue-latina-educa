<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobOfferCv extends Model
{
  use SoftDeletes;

  protected $table = 'job_opportunity_user_cv';

  protected $fillable = [
    'version',
    'url',
    'user_id'
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  protected $casts = [];

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }
}
