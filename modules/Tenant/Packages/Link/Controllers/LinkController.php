<?php

namespace Modules\Tenant\Packages\Link\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Link\UseCases\LinkUseCases;

class LinkController extends Controller
{
    public function create(Request $request)
    {
        return LinkUseCases::create($request);
    }

    public function delete(int $id, string $model)
    {
        return LinkUseCases::delete($id, $model);
    }
}
