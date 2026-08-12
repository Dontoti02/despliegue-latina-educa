<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class JobOfferState extends Model
{
  protected $table = 'job_opportunity_offer_state';

  protected $fillable = [
    'name',
    'key',
    'description',
    'created_at',
    'updated_at'
  ];

  protected $hidden = [
    'created_at',
    'updated_at'
  ];

  public function offers()
  {
    return $this->belongsToMany(JobOffer::class, 'job_opportunity_offer_state_detail', 'state_id', 'offer_id');
  }
}
