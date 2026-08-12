<?php

namespace Modules\Tenant\Packages\SyllabusManager\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\SyllabusManager\Repositories\SyllabusProgressRepository;

class SyllabusProgressUseCases
{
    public static function list(Request $request)
    {
        try {
            $result = SyllabusProgressRepository::list($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }

    public static function download(string $type, Request $request)
    {
        try {
            [$binary, $type, $filename] = SyllabusProgressRepository::download($type, $request);
            return Response::file($binary, $type, $filename);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
