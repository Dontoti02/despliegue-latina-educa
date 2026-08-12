<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $table = 'inc_incident';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'subject',
        'description',
        'incident_type_id',
        'file_url',
        'status_id',
        'user_id',
        'incident_number',
        'register_date',
        'completion_date',
        'conclusion',
        'admin_user_id',
    ];

    protected $casts = [
        'register_date' => 'datetime',
        'completion_date' => 'datetime'
    ];

    public function observations()
    {
        return $this->hasMany(IncidentObservation::class, 'incident_id');
    }

    public function status()
    {
        return $this->hasOne(IncidentStatus::class,'id', 'status_id');
    }
    
    public function type()
    {
        return $this->hasOne(IncidentType::class, 'id', 'incident_type_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function adminUser()
    {
        return $this->hasOne(User::class, 'id', 'admin_user_id');
    }

}
