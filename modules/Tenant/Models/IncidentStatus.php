<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentStatus extends Model
{
    protected $table = 'inc_status';
    public $timestamps = false;
    protected $fillable = [
        'id',
        'name',
        'closes_incident'
    ];

    protected $casts = [
        'closes_incident' => 'boolean',
    ];

    public function incidents()
    {
        return $this->hasMany(Incident::class, 'status_id');
    }

}
