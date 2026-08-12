<?php

namespace Modules\Tenant\Packages\Cycle\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Cycle\UseCases\CycleUseCases;

class CycleController extends Controller
{
    public function list(Request $request)
    {
        return CycleUseCases::list($request);
    }

    public function create(Request $request)
    {
        return CycleUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return CycleUseCases::update($id, $request);
    }

    public function sort(int $id, int $position)
    {
        return CycleUseCases::sort($id, $position);
    }

    public function delete(int $id)
    {
        return CycleUseCases::delete($id);
    }
}
