<?php

namespace Modules\Tenant\Packages\EvaluationGroup\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\EvaluationGroup\UseCases\EvaluationGroupUseCases;

class EvaluationGroupController extends Controller
{
    public function list(int $classroomId)
    {
        return EvaluationGroupUseCases::list($classroomId);
    }

    public function set(Request $request)
    {
        return EvaluationGroupUseCases::set($request);
    }
}
