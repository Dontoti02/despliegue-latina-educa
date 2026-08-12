<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use SoftDeletes;

    protected $table = 'form';

    protected $fillable = [
        'id',
        'content_id',
        'uuid',
        'title',
        'description',
        'score_max',
    ];

    protected $casts = [
        'score_max' => 'float',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'form_id');
    }

    public function responses()
    {
        return $this->hasMany(FormResponse::class, 'form_id');
    }
}
