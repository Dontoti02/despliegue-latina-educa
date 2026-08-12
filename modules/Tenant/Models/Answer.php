<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Answer extends Model
{
    use SoftDeletes;

    protected $table = 'answer';

    protected $fillable = [
        'id',
        'person_id',
        'content_id',
        'status',
        'score',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function links()
    {
        return $this->morphMany(Link::class, 'linkable');
    }
}
