<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnrollmentType extends Model
{
    use SoftDeletes;

    protected $table = 'enrollment_type';

    protected $fillable = [
        'id',
        'name',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'type_id');
    }
}
