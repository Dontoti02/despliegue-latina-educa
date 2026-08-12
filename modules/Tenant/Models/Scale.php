<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class Scale extends Model
{
    protected $table = 'scale';

    protected $fillable = [
        'id',
        'name',
        'scale_amount',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'scale_id');
    }
}
