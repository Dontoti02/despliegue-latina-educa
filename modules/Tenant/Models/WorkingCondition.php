<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkingCondition extends Model
{
    use SoftDeletes;

    protected $table = 'working_condition';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'working_condition_id');
    }
}
