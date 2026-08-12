<?php

namespace Modules\Tenant\Packages\StudyPlan\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyPlan\UseCases\StudyPlanUseCases;

class StudyPlanController extends Controller
{
    public function params()
    {
        return StudyPlanUseCases::params();
    }

    public function list(Request $request)
    {
        return StudyPlanUseCases::list($request);
    }

    public function create(Request $request)
    {
        return StudyPlanUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return StudyPlanUseCases::update($id, $request);
    }

    public function toggle(int $id)
    {
        return StudyPlanUseCases::toggle($id);
    }

    public function delete(int $id)
    {
        return StudyPlanUseCases::delete($id);
    }

    public function curriculum(int $id)
    {
        return StudyPlanUseCases::curriculum($id);
    }
}
