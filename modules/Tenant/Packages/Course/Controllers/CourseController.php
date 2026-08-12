<?php

namespace Modules\Tenant\Packages\Course\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Course\UseCases\CourseUseCases;

class CourseController extends Controller
{
    public function params()
    {
        return CourseUseCases::params();
    }

    public function list(Request $request)
    {
        return CourseUseCases::list($request);
    }

    public function all(Request $request)
    {
      return CourseUseCases::all($request);
    }

    public function create(Request $request)
    {
        return CourseUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return CourseUseCases::update($id, $request);
    }

    public function toggle(int $id)
    {
        return CourseUseCases::toggle($id);
    }

    public function delete(int $id)
    {
        return CourseUseCases::delete($id);
    }
}
