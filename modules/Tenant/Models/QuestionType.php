<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{
    protected $table = 'question_type';

    protected $fillable = [
        'id',
        'key',
        'name',
        'data_type',
        'is_gradable',
        'order_number',
        'options'
    ];

    protected $casts = [
        'is_gradable' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
