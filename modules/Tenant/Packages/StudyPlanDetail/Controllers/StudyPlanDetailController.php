<?php

namespace Modules\Tenant\Packages\StudyPlanDetail\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyPlanDetail\UseCases\StudyPlanDetailUseCases;

class StudyPlanDetailController extends Controller
{
    public function detail(int $studyPlanId)
    {
        return StudyPlanDetailUseCases::detail($studyPlanId);
    }

    public function params(int $studyPlanId)
    {
        return StudyPlanDetailUseCases::params($studyPlanId);
    }

    public function assign(Request $request)
    {
        return StudyPlanDetailUseCases::assign($request);
    }

    public function unassign(int $id)
    {
        return StudyPlanDetailUseCases::unassign($id);
    }
}
