<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModuleType extends Model
{
    use SoftDeletes;

    protected $table = 'module_type';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function modules()
    {
        return $this->hasMany(Module::class, 'type_id');
    }
}
