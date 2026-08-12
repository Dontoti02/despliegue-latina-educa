<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class ImportDetail extends Model
{
    protected $table = 'import_detail';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'import_id',
        'is_current',
        'is_active',
        'status',
        'status',
        'progress',
        'date',
        'time_elapsed',
        'log',
        'summary',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function import()
    {
        return $this->belongsTo(Import::class, 'import_id');
    }
}
