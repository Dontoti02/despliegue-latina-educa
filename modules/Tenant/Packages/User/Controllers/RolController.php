<?php

namespace Modules\Tenant\Packages\User\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\User\UseCases\RolUseCases;

class RolController extends Controller
{
    public function list(Request $request)
    {
        return RolUseCases::list($request);
    }

    public function change(int $id)
    {
        return RolUseCases::change($id);
    }
}
