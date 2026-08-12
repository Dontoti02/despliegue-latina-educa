<?php

namespace Modules\Tenant\Packages\MeritOrder\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\MeritOrder\UseCases\MeritOrderUseCases;

class MeritOrderController extends Controller
{
    public function filters()
    {
        return MeritOrderUseCases::filters();
    }

    public function list(Request $request)
    {
        return MeritOrderUseCases::list($request);
    }
}
