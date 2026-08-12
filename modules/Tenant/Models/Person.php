<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'person';

    protected $fillable = [
        'id',
        'document_type',
        'document_number',
        'names',
        'phone',
        'sex',
        'birth_date',
        'native_language',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function additionalData()
    {
        return $this->hasOne(AdditionalData::class, 'person_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'person_id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'person_id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'person_id');
    }

    public function publications()
    {
        return $this->hasMany(Publication::class, 'person_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'person_id');
    }

    public function family()
    {
        return $this->hasMany(Family::class, 'person_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class, 'person_id');
    }

    public function movementRegistrations()
    {
        return $this->hasMany(Movement::class, 'person_registration_id');
    }

    public function movementDetailRegistrationPayments()
    {
        return $this->hasMany(MovementDetails::class, 'person_registration_payment_id');
    }

    public function movementDetailCreatedSchedulesBy()
    {
        return $this->hasMany(MovementDetails::class, 'person_created_schedule_by');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'person_id');
    }

    public function assistances()
    {
        return $this->hasMany(Assistance::class, 'person_id');
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'person_id');
    }

    public function averages()
    {
        return $this->hasMany(Average::class, 'person_id');
    }

    public function averageDetails()
    {
        return $this->hasMany(AverageDetail::class, 'person_id');
    }
}
