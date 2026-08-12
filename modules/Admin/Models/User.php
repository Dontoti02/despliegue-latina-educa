<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Helpers\SessionManager;

class User extends Model
{
    use SoftDeletes;

    protected $table = 'user';

    protected $fillable = [
        'rol_id',
        'email',
        'password',
        'names',
        'remember_token',
        'reset_password_token',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_user');
    }

    public function scopeByKey($query, $key, $value)
    {
        $result = $query->where($key, $value)->first();

        if (!$result) {
            throw new Exception('Usuario no encontrado');
        }

        return $result;
    }
}
