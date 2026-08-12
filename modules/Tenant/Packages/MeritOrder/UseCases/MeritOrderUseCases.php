<?php

namespace Modules\Tenant\Packages\MeritOrder\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\MeritOrder\Repositories\MeritOrderRepository;

class MeritOrderUseCases
{
    public static function filters()
    {
        try {
            $result = MeritOrderRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = MeritOrderRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
