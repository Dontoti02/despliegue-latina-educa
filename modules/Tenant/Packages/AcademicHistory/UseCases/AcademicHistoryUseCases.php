<?php

namespace Modules\Tenant\Packages\AcademicHistory\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\AcademicHistory\Repositories\AcademicHistoryRepository;

class AcademicHistoryUseCases
{
    public static function filters(int $studentId)
    {
        try {
            $result = AcademicHistoryRepository::filters($studentId);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function list(Request $request)
    {
        try {
            $result = AcademicHistoryRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function download(string $type, Request $request)
    {
        try {
            [$binary, $type, $filename] = AcademicHistoryRepository::download($type, $request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
