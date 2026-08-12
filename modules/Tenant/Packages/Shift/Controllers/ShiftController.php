<?php

namespace Modules\Tenant\Packages\Shift\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Shift\UseCases\ShiftUseCases;

class ShiftController extends Controller
{
    public function list(Request $request)
    {
        return ShiftUseCases::list($request);
    }

    public function create(Request $request)
    {
        return ShiftUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ShiftUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return ShiftUseCases::delete($id);
    }
}
