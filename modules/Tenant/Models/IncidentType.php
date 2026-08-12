<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentType extends Model
{
    protected $table = 'inc_incident_type';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name',
    ];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'incident_type_id');
    }

}
