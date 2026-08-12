<?php

namespace Modules\Tenant\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TrainingAnswer extends Model
{
    protected $table = 'training_answer';

    protected $fillable = [
        'person_id',
        'training_content_id',
        'status',
        'score',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(TrainingContent::class,'training_content_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function links(): MorphMany
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Respuesta no encontrada');
        }

        return $result;
    }
}
