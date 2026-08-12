<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolUser extends Model
{
    protected $table = 'rol_user';

    protected $fillable = [
        'rol_id',
        'user_id',
    ];

    public $timestamps = false;
    
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
