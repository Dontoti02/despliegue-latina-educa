<?php

namespace Modules\Tenant\Packages\EvaluationForm\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\EvaluationForm\UseCases\EvaluationFormUseCases;

class EvaluationFormController extends Controller
{
    public function questionTypes()
    {
        return EvaluationFormUseCases::questionTypes();
    }

    public function get(string $uuid, int $person_id = 0)
    {
        return EvaluationFormUseCases::get($uuid, $person_id);
    }

    public function delivered(Request $request)
    {
        return EvaluationFormUseCases::delivered($request);
    }
}
