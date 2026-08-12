<?php

namespace Modules\Tenant\Packages\ModuleType\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\ModuleType\UseCases\ModuleTypeUseCases;

class ModuleTypeController extends Controller
{
    public function list(Request $request)
    {
        return ModuleTypeUseCases::list($request);
    }

    public function create(Request $request)
    {
        return ModuleTypeUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ModuleTypeUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return ModuleTypeUseCases::delete($id);
    }
}
