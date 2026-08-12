<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $table = 'file';

    protected $fillable = [
        'id',
        'fileable_id',
        'fileable_type',
        'name',
        'url',
        'metadata',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new \Exception('Archivo no encontrado.');
        }

        return $result;
    }

    public function fileable()
    {
        return $this->morphTo();
    }
}
