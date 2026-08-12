<?php

namespace Modules\Tenant\Packages\SyllabusManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\SyllabusManager\UseCases\SyllabusProgressUseCases;

class SyllabusProgressController extends Controller
{
    public function list(Request $request)
    {
        return SyllabusProgressUseCases::list($request);
    }

    public function download(string $type, Request $request)
    {
        return SyllabusProgressUseCases::download($type, $request);
    }
}
