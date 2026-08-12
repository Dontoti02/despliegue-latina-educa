<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tema del banco de preguntas.
 *
 * Etiqueta independiente: no se asocia a ninguna entidad académica existente.
 */
class QuestionTopic extends Model
{
    use SoftDeletes;

    protected $table = 'question_topic';

    protected $fillable = [
        'id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionBank::class,
            'question_bank_topic',
            'question_topic_id',
            'question_bank_id'
        );
    }
}
