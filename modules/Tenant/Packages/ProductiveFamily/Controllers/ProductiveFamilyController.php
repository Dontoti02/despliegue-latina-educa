<?php

namespace Modules\Tenant\Packages\ProductiveFamily\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\ProductiveFamily\UseCases\ProductiveFamilyUseCases;

class ProductiveFamilyController extends Controller
{
    public function list(Request $request)
    {
        return ProductiveFamilyUseCases::list($request);
    }

    public function create(Request $request)
    {
        return ProductiveFamilyUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return ProductiveFamilyUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return ProductiveFamilyUseCases::delete($id);
    }
}
