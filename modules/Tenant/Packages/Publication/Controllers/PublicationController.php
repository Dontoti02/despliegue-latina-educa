<?php

namespace Modules\Tenant\Packages\Publication\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Publication\UseCases\PublicationUseCases;

class PublicationController extends Controller
{
    public function list(Request $request)
    {
        return PublicationUseCases::list($request);
    }

    public function create(Request $request)
    {
        return PublicationUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return PublicationUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return PublicationUseCases::delete($id);
    }
}
