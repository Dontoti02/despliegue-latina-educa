<?php

namespace Modules\Tenant\Packages\Training\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Training\Repositories\TrainingReportRepository;

class TrainingReportUseCases
{
    public static function filters()
    {
        try {
            $result = TrainingReportRepository::filters();
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = TrainingReportRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function listDownload(Request $request)
    {
        try {
            [$binary, $type, $filename] = TrainingReportRepository::listDownload($request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
