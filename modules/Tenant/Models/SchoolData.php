<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolData extends Model
{
    protected $table = 'school_data';

    public $timestamps = false;

    protected $fillable = [
        'modular_code',
        'name',
        'start_date',
        'end_date',
        'type',
        'category',
        'CEVA_certificate',
        'condition',
        'observations',
        'photo',
        'academic_validation',
        'person_id'
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
