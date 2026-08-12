<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Average extends Model
{
    use SoftDeletes;

    protected $table = 'average';

    protected $fillable = [
        'id',
        'person_id',
        'evaluation_group_id',
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

    public function evaluationGroup()
    {
        return $this->belongsTo(EvaluationGroup::class, 'evaluation_group_id');
    }
}
