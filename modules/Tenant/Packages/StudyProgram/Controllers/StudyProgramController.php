<?php

namespace Modules\Tenant\Packages\StudyProgram\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\StudyProgram\UseCases\StudyProgramUseCases;

class StudyProgramController extends Controller
{
    public function params()
    {
        return StudyProgramUseCases::params();
    }

    public function list(Request $request)
    {
        return StudyProgramUseCases::list($request);
    }

    public function create(Request $request)
    {
        return StudyProgramUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return StudyProgramUseCases::update($id, $request);
    }

    public function toggle(int $id)
    {
        return StudyProgramUseCases::toggle($id);
    }

    public function delete(int $id)
    {
        return StudyProgramUseCases::delete($id);
    }
}
