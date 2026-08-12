<?php

namespace Modules\Admin\Helpers;

use Illuminate\Support\Facades\DB;
use Modules\Shared\Enum\MessageResponse;

class InstitutionStorageHelper
{
    public static function find(int $institutionId)
    {

        $storage = DB::connection('central')->table('institution_storage')
            ->where('institution_id', $institutionId)
            ->first();

        if (!$storage) {
            $message = str_replace("{var}", "espacio de almacenamiento", MessageResponse::notFound->value);
            throw new \Exception($message, 404);
        }

        return $storage;
    }

    public static function hasSpace($institutionId, $totalFileSizeMb)
    {

        $storageInfo = InstitutionStorageHelper::find($institutionId);

        $usedSpaceMb = $storageInfo->used_space_mb;
        $limitSpaceMb = $storageInfo->limit_space_mb;
        $newUsedSpaceMb = $usedSpaceMb + $totalFileSizeMb;

        return $newUsedSpaceMb <= $limitSpaceMb;
    }

    public static function updateUsedSpaceInstitution($institutionId, $totalFileSizeMb)
    {
        $storageInfo = InstitutionStorageHelper::find($institutionId);

        $usedSpaceMb = $storageInfo->used_space_mb;
        $limitSpaceMb = $storageInfo->limit_space_mb;
        $newUsedSpaceMb = $usedSpaceMb + $totalFileSizeMb;
        $newFreeSpaceMb = $limitSpaceMb - $newUsedSpaceMb;

        DB::connection('central')->table('institution_storage')
            ->where('institution_id', $institutionId)
            ->update([
                'used_space_mb' => $newUsedSpaceMb,
                'free_space_mb' => $newFreeSpaceMb,
            ]);
    }
}
