<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormResponse extends Model
{
    use SoftDeletes;

    protected $table = 'form_response';

    protected $fillable = [
        'id',
        'form_id',
        'person_id',
        'questions',
        'score',
    ];

    protected $casts = [
        'questions' => 'array',
        'score' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
