<?php

namespace Modules\Tenant\Packages\WorkingCondition\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\WorkingCondition\UseCases\WorkingConditionUseCases;

class WorkingConditionController extends Controller
{
    public function list(Request $request)
    {
        return WorkingConditionUseCases::list($request);
    }

    public function create(Request $request)
    {
        return WorkingConditionUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return WorkingConditionUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return WorkingConditionUseCases::delete($id);
    }
}
