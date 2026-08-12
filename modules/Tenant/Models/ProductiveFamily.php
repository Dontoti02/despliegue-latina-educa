<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductiveFamily extends Model
{
    use SoftDeletes;

    protected $table = 'productive_family';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class, 'productive_family_id');
    }
}
