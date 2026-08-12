<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentGroup extends Model
{
    use SoftDeletes;

    protected $table = 'content_group';

    protected $fillable = [
        'id',
        'classroom_id',
        'title',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function contents()
    {
        return $this->hasMany(Content::class, 'content_group_id');
    }

    public function averageDetails()
    {
        return $this->hasMany(AverageDetail::class, 'content_group_id');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
