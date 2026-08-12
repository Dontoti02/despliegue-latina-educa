<?php

namespace Modules\Tenant\Packages\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Training\UseCases\TrainingEvaluationUseCases;

class TrainingEvaluationController extends Controller
{
    public function list(int $training_id, int $person_id = 0)
    {
        return TrainingEvaluationUseCases::list($training_id, $person_id);
    }

    public function evaluate(Request $request)
    {
        return TrainingEvaluationUseCases::evaluate($request);
    }
}
