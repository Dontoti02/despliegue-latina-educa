<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAverage extends Model
{
    protected $table = 'training_average';

    protected $fillable = [
        'person_id',
        'training_evaluation_group_id',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function evaluationGroup(): BelongsTo
    {
        return $this->belongsTo(TrainingEvaluationGroup::class);
    }
}
