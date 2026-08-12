<?php

namespace Modules\Tenant\Packages\Teacher\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Teacher\UseCases\TeacherUseCases;

class TeacherController extends Controller
{
    public function search(Request $request)
    {
        return TeacherUseCases::search($request);
    }

    public function params()
    {
        return TeacherUseCases::params();
    }

    public function list(Request $request)
    {
        return TeacherUseCases::list($request);
    }

    public function create(Request $request)
    {
        return TeacherUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return TeacherUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return TeacherUseCases::delete($id);
    }
}
