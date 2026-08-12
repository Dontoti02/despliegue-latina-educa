<?php

namespace Modules\Tenant\Packages\User\Repositories;

use Exception;
use Modules\Tenant\Models\Rol;
use Modules\Tenant\Models\User;

class RolRepository
{
    public static function list()
    {
        $roles = Rol::all();

        return $roles;
    }

    public static function change(int $id)
    {
        $user = User::authenticated();

        $rol = Rol::findOrFail($id);

        $roles = $user->roles()->pluck('rol.id')->toArray();

        if (!in_array($rol->id, $roles)) {
            throw new Exception("No tienes el rol de $rol->name");
        }

        $user->update([
            'rol_id' => $rol->id
        ]);

        return $rol->id;
    }
}
