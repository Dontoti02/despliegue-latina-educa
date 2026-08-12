<?php

namespace Modules\Tenant\Packages\EvaluationGroup\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\EvaluationGroup\Repositories\EvaluationGroupRepository;

class EvaluationGroupUseCases
{
    public static function list(int $classroomId)
    {
        try {
            $result = EvaluationGroupRepository::list($classroomId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function set(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = EvaluationGroupRepository::set($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
