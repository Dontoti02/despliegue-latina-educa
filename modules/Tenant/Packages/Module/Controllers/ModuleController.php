<?php

namespace Modules\Tenant\Packages\Module\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Module\UseCases\ModuleUseCases;

class ModuleController extends Controller
{
    public function params()
    {
        return ModuleUseCases::params();
    }

    public function list(Request $request)
    {
        return ModuleUseCases::list($request);
    }

    public function create(Request $request)
    {
        return ModuleUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ModuleUseCases::update($id, $request);
    }

    public function sort(int $id, int $position)
    {
        return ModuleUseCases::sort($id, $position);
    }

    public function delete(int $id)
    {
        return ModuleUseCases::delete($id);
    }
}
