<?php

namespace Modules\Tenant\Packages\Period\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Period\UseCases\PeriodUseCases;

class PeriodController extends Controller
{
    public function list(Request $request)
    {
        return PeriodUseCases::list($request);
    }

    public function create(Request $request)
    {
        return PeriodUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return PeriodUseCases::update($id, $request);
    }

    public function toggle(int $id)
    {
        return PeriodUseCases::toggle($id);
    }

    public function delete(int $id)
    {
        return PeriodUseCases::delete($id);
    }
}
