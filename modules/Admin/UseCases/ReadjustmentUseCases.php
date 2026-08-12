<?php

namespace Modules\Admin\UseCases;

use Exception;
use Modules\Admin\Repositories\ReadjustmentRepository;
use Modules\Shared\Utils\Response;

class ReadjustmentUseCases
{
    public static function run()
    {
        try {
            $result = ReadjustmentRepository::run();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function syncMigrationsTableToAllTenants()
    {
        try {
            $result = ReadjustmentRepository::syncBaseMigrationsTenant();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
