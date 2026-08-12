<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use SoftDeletes;

    protected $table = 'link';

    protected $fillable = [
        'id',
        'linkable_id',
        'linkable_type',
        'url',
    ];

    public function linkable()
    {
        return $this->morphTo();
    }
}
