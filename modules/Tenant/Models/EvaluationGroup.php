<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationGroup extends Model
{
    use SoftDeletes;

    protected $table = 'evaluation_group';

    protected $fillable = [
        'id',
        'classroom_id',
        'title',
        'weight',
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
        return $this->hasMany(Content::class, 'evaluation_group_id');
    }

    public function averages()
    {
        return $this->hasMany(Average::class, 'evaluation_group_id');
    }

    public function averageDetails()
    {
        return $this->hasMany(AverageDetail::class, 'evaluation_group_id');
    }
}
