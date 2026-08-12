<?php

namespace Modules\Tenant\Packages\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Evaluation\UseCases\EvaluationUseCases;

class EvaluationController extends Controller
{
    public function list(int $classroom_id, int $person_id = 0)
    {
        return EvaluationUseCases::list($classroom_id, $person_id);
    }

    public function evaluate(Request $request)
    {
        return EvaluationUseCases::evaluate($request);
    }
}
