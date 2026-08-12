<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionBank extends Model
{
    use SoftDeletes;

    protected $table = 'question_bank';

    protected $fillable = [
        'id',
        'uuid',
        'question_type_key',
        'label',
        'options',
        'score_max',
        'difficulty',
        'created_by_person_id',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'options' => 'array',
        'score_max' => 'float',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'created_by_person_id');
    }

    /** Unidades Didácticas etiquetadas (tabla `course` existente). */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(
            Course::class,
            'question_bank_course',
            'question_bank_id',
            'course_id'
        );
    }

    /** Asignaturas etiquetadas. */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionSubject::class,
            'question_bank_subject',
            'question_bank_id',
            'question_subject_id'
        );
    }

    /** Temas etiquetados. */
    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(
            QuestionTopic::class,
            'question_bank_topic',
            'question_bank_id',
            'question_topic_id'
        );
    }
}
