<?php

namespace Modules\Tenant\Packages\StudyPlanType\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyPlanType\UseCases\StudyPlanTypeUseCases;

class StudyPlanTypeController extends Controller
{
    public function list(Request $request)
    {
        return StudyPlanTypeUseCases::list($request);
    }

    public function create(Request $request)
    {
        return StudyPlanTypeUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return StudyPlanTypeUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return StudyPlanTypeUseCases::delete($id);
    }
}
