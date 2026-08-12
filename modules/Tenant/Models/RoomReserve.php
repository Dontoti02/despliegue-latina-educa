<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomReserve extends Model
{
    use SoftDeletes;

    protected $table = 'room_reserve';

    protected $fillable = [
        'id',
        'room_id',
        'date',
        'hour_start',
        'hour_end',
        'applicant',
        'motive',
        'is_confirmed',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'date_start' => 'datetime',
        'date_end' => 'datetime',
        'is_confirmed' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
