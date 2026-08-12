<?php

namespace Modules\Tenant\Packages\Classroom\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Classroom\UseCases\ClassroomUseCases;

class ClassroomController extends Controller
{
    public function params()
    {
        return ClassroomUseCases::params();
    }

    public function list(Request $request)
    {
        return ClassroomUseCases::list($request);
    }

    public function detail(int $id)
    {
        return ClassroomUseCases::detail($id);
    }

    public function listCourses(Request $request)
    {
        return ClassroomUseCases::listCourses($request);
    }

    public function create(Request $request)
    {
        return ClassroomUseCases::create($request);
    }

    public function delete(int $id)
    {
        return ClassroomUseCases::delete($id);
    }

    public function updateImage(int $id, Request $request)
    {
        return ClassroomUseCases::updateImage($id, $request);
    }

    public function updateFavorite(int $id)
    {
        return ClassroomUseCases::updateFavorite($id);
    }

    public function enroll(int $id, int $studentId)
    {
        return ClassroomUseCases::enroll($id, $studentId);
    }

    public function remove(int $id, int $studentId)
    {
        return ClassroomUseCases::remove($id, $studentId);
    }

    public function toggle(int $id)
    {
        return ClassroomUseCases::toggle($id);
    }
}
