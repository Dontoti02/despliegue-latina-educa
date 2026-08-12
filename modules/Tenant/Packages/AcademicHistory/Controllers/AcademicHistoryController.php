<?php

namespace Modules\Tenant\Packages\AcademicHistory\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\AcademicHistory\UseCases\AcademicHistoryUseCases;

class AcademicHistoryController extends Controller
{
    public function filters(int $studentId)
    {
        return AcademicHistoryUseCases::filters($studentId);
    }

    public function list(Request $request)
    {
        return AcademicHistoryUseCases::list($request);
    }

    public function download(string $type, Request $request)
    {
        return AcademicHistoryUseCases::download($type, $request);
    }
}
