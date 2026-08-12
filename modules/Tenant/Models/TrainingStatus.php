<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingStatus extends Model
{
    protected $table = 'training_status';

    protected $fillable = [
        'id',
        'name',
    ];
}
