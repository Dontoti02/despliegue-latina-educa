<?php

namespace Modules\Tenant\Packages\User\UseCases;

use Exception;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\User\Repositories\MenuRepository;

class MenuUseCases
{
    public static function get()
    {
        try {
            $result = MenuRepository::get();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
