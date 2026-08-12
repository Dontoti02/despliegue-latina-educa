<?php

namespace Modules\Shared\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Admin\Models\Institution;

class AuditLog extends Model
{
    protected $connection = 'audit_db';
    protected $table = 'audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'institution_id',
        'user_id',
        'user_email',
        'event_type',
        'method',
        'request_url',
        'request_body',
        'status_code',
        'response_message',
        'ip_address',
        'user_agent',
        'trace_id',
        'created_at',
    ];

    protected $casts = [
        'request_body' => 'array',
        'created_at'   => 'datetime',
    ];

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id', 'id');
    }
}