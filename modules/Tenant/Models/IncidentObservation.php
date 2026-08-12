<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentObservation extends Model
{
    protected $table = 'inc_incident_observation';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'request',
        'response',
        'file_url',
        'incident_id',
        'admin_user_id',
    ];

    public function incidents()
    {
        return $this->BelongsTo(Incident::class, 'incident_id');
    }

    public function adminUser()
    {
        return $this->BelongsTo(User::class, 'admin_user_id');
    }

}
