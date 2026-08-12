<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Content extends Model
{
    use SoftDeletes;

    protected $table = 'content';

    protected $fillable = [
        'id',
        'content_group_id',
        'evaluation_group_id',
        'title',
        'description',
        'type',
        'is_visible',
        'date_start',
        'date_limit',
        'is_open',
        'score',
        'has_evaluation_form',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_open' => 'boolean',
        'score' => 'float',
        'has_evaluation_form' => 'boolean',
    ];

    public function contentGroup()
    {
        return $this->belongsTo(ContentGroup::class, 'content_group_id');
    }

    public function evaluationGroup()
    {
        return $this->belongsTo(EvaluationGroup::class, 'evaluation_group_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'content_id');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function links()
    {
        return $this->morphMany(Link::class, 'linkable');
    }

    public function form()
    {
        return $this->hasOne(Form::class, 'content_id');
    }
}
