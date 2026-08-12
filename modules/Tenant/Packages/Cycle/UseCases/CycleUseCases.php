<?php

namespace Modules\Tenant\Packages\Cycle\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Cycle\Repositories\CycleRepository;

class CycleUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = CycleRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = CycleRepository::create($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function update(int $id, Request $request)
    {
        DB::beginTransaction();
        try {
            $result = CycleRepository::update($id, $request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function sort(int $id, int $position)
    {
        DB::beginTransaction();
        try {
            $result = CycleRepository::sort($id, $position);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $result = CycleRepository::delete($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
