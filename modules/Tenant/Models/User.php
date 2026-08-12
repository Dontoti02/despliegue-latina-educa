<?php

namespace Modules\Tenant\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Modules\Shared\Helpers\SessionManager;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $table = 'user';

    protected $fillable = [
        'id',
        'person_id',
        'rol_id',
        'company_id',
        'email',
        'password',
        'remember_token',
        'reset_password_token',
        'default_user',
        'is_active',
        'last_login',
        'avatar',
        'attempts',
        'last_attempt',
    ];

    public function scopeAuthenticated($query, $roles = [])
    {
        $session = SessionManager::get();

        $result = $query->find($session->id);

        if (!$result) {
            throw new Exception('Usuario no encontrado.');
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $loginRol = Rol::select()
            ->where('id', $result->rol_id)
            ->whereIn('id', $roles)
            ->exists();

        if (count($roles) > 0 && !$loginRol) {
            throw new Exception('¡Usuario no autorizado!');
        }

        return $result;
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Usuario no encontrado.');
        }

        return $result;
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_user');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(JobCompany::class, 'company_id');
    }

    public function cvs(): HasMany
    {
        return $this->hasMany(JobOfferCv::class, 'user_id');
    }
}
