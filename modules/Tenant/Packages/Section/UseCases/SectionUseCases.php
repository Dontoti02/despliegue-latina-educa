<?php

namespace Modules\Tenant\Packages\Section\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Section\Repositories\SectionRepository;

class SectionUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = SectionRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function create(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = SectionRepository::create($request);
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
            $result = SectionRepository::update($id, $request);
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
            $result = SectionRepository::delete($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
