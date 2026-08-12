<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Asignatura del banco de preguntas.
 *
 * Etiqueta independiente: no se asocia a ninguna entidad académica existente.
 */
class QuestionSubject extends Model
{
    use SoftDeletes;

    protected $table = 'question_subject';

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
            'question_bank_subject',
            'question_subject_id',
            'question_bank_id'
        );
    }
}
