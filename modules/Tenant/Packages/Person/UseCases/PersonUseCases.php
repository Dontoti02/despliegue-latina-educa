<?php

namespace Modules\Tenant\Packages\Person\UseCases;

use Exception;
use Illuminate\Http\Request;
use Modules\Shared\Utils\Response;
use Modules\Tenant\Packages\Person\Repositories\PersonRepository;

class PersonUseCases
{
    public static function search(Request $request)
    {
        try {
            $result = PersonRepository::search($request);
            return Response::success($result);
        } catch (Exception $e) {
            return Response::error($e);
        }
    }
}
