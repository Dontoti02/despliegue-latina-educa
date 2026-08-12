<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use SoftDeletes;

    protected $table = 'comment';

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'person_id',
        'value',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
