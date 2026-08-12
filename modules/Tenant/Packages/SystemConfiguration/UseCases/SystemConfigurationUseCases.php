<?php

namespace Modules\Tenant\Packages\SystemConfiguration\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\SystemConfiguration\Repositories\SystemConfigurationRepository;

class SystemConfigurationUseCases
{
    public static function general()
    {
        try {
            $result = SystemConfigurationRepository::general();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function landingPage()
    {
        try {
            $result = SystemConfigurationRepository::landingPage();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list()
    {
        try {
            $result = SystemConfigurationRepository::list();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function update(string $key, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = SystemConfigurationRepository::update($key, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function uploadImage(Request $request)
    {
        try {
            $result = SystemConfigurationRepository::uploadImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function deleteImage(Request $request)
    {
        try {
            $result = SystemConfigurationRepository::deleteImage($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
