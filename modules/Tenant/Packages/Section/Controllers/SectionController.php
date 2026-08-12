<?php

namespace Modules\Tenant\Packages\Section\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Section\UseCases\SectionUseCases;

class SectionController extends Controller
{
    public function list(Request $request)
    {
        return SectionUseCases::list($request);
    }

    public function create(Request $request)
    {
        return SectionUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return SectionUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return SectionUseCases::delete($id);
    }
}
