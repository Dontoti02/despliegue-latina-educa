<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Model
{
    use SoftDeletes;

    protected $table = 'option';

    protected $fillable = [
        'id',
        'option_id',
        'menu_id',
        'name',
        'name_url',
        'icon',
        'is_visible',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id');
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_option');
    }
}
