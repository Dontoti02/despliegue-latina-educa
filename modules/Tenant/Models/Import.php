<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $table = 'import';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'key',
        'title',
        'attributes',
    ];

    public function details()
    {
        return $this->hasMany(ImportDetail::class, 'import_id');
    }
}
