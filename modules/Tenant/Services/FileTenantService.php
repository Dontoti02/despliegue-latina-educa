<?php

namespace Modules\Tenant\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Enum\MessageResponse;
use Modules\Shared\Services\FileSystemService;
use Modules\Shared\Utils\MetadataFile;
use Modules\Tenant\Models\File;
use Illuminate\Support\Str;
use Modules\Admin\Helpers\InstitutionStorageHelper;
use Modules\Shared\Services\CentralTransactionService;

class FileTenantService
{
    public static function save(Model $model, $file, string $path = '')
    {
        try {

            $tenantId = tenant('id');

            $institution = CentralTransactionService::institution($tenantId);

            $totalSizeFilesMb = 0.00;

            $metadata = MetadataFile::get($file);

            $totalSizeFilesMb += $metadata['size'];

            if (!InstitutionStorageHelper::hasSpace($institution->id, $totalSizeFilesMb)) {
                throw new \Exception(MessageResponse::spaceLimitExceeded->value, 500);
            }

            $folderName = $institution->storage->folder_name;

            if ($path !== '') {
                $folderName .= '/' . $path;
            }

            $name = (string) Str::uuid();

            $fileUrl = FileSystemService::create($folderName, $file);

            $file = new File();

            $file->name = $name;

            $file->url = $fileUrl;

            $file->metadata = json_encode($metadata);

            $model->files()->save($file);
            
            InstitutionStorageHelper::updateUsedSpaceInstitution($institution->id, $totalSizeFilesMb);

            return $file;
        } catch (\Exception $th) {
          throw $th;
        }
    }

    public static function delete(array $files)
    {
        try {

            $tenantId = tenant('id');

            $institution = CentralTransactionService::institution($tenantId);

            $totalSizeFilesMb = 0.00;

            foreach ($files as $file) {

                $metadata = json_decode($file->metadata);

                $totalSizeFilesMb += $metadata->size;
                $url = $file->url;

                $file->delete();

                FileSystemService::delete($url);
            }

            InstitutionStorageHelper::updateUsedSpaceInstitution($institution->id, -$totalSizeFilesMb);
        } catch (\Exception $th) {
            throw $th;
        }
    }
}
