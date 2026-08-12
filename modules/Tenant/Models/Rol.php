<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{
    use SoftDeletes;

    protected $table = 'rol';

    protected $fillable = [
        'id',
        'name',
        'key',
        'level',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'rol_user');
    }

    public function options()
    {
        return $this->belongsToMany(Option::class, 'rol_option');
    }
}
