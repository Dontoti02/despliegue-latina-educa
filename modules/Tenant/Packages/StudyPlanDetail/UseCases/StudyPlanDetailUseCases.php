<?php

namespace Modules\Tenant\Packages\StudyPlanDetail\UseCases;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\StudyPlanDetail\Repositories\StudyPlanDetailRepository;

class StudyPlanDetailUseCases
{
    public static function detail(int $studyPlanId)
    {
        try {
            $result = StudyPlanDetailRepository::detail($studyPlanId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function params(int $studyPlanId)
    {
        try {
            $result = StudyPlanDetailRepository::params($studyPlanId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function assign(Request $request)
    {
        DB::beginTransaction();
        try {
            $result = StudyPlanDetailRepository::assign($request);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }

    public static function unassign(int $id)
    {
        DB::beginTransaction();
        try {
            $result = StudyPlanDetailRepository::unassign($id);
            DB::commit();
            return Response::success($result);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::error($e->getMessage());
        }
    }
}
