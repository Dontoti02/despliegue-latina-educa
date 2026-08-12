<?php

namespace Modules\Tenant\Packages\User\Repositories;

use Modules\Tenant\Packages\SystemConfiguration\Helpers\SystemConfigurationHelper;
use Modules\Tenant\Models\User;
use Modules\Tenant\Packages\Period\Helpers\PeriodHelper;

class ProfileRepository
{
    public static function get()
    {
        $user = User::authenticated();

        $roles = $user->roles()
            ->select('rol.id', 'rol.name')
            ->get();

        $period = PeriodHelper::current();

        $size_mb = SystemConfigurationHelper::getValueByKey('maximum_file_size_to_upload');

        $mimes = SystemConfigurationHelper::getValueByKey('extensions_allowed_to_upload');
        $mimes = array_filter($mimes, function ($item) {
            return $item->permitted == true;
        });
        $mimes = array_values($mimes);
        $mimes = array_map(function ($item) {
            return $item->extension;
        }, $mimes);

        $result = [
            'user'  => [
                'document_number' => $user->person->document_number,
                'names' => $user->person->names,
                'phone' => $user->person->phone,
                'email' => $user->email,
                'photo' => $user->avatar,
            ],
            'current_role' => $user->rol_id,
            'roles' => $roles,
            'period' => $period,
            'maximum_file_size_to_upload' => $size_mb,
            'extensions_allowed_to_upload' => $mimes,
        ];

        return $result;
    }
}
