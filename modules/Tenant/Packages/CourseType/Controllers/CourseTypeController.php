<?php

namespace Modules\Tenant\Packages\CourseType\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\CourseType\UseCases\CourseTypeUseCases;

class CourseTypeController extends Controller
{
    public function list(Request $request)
    {
        return CourseTypeUseCases::list($request);
    }

    public function create(Request $request)
    {
        return CourseTypeUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return CourseTypeUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return CourseTypeUseCases::delete($id);
    }
}
