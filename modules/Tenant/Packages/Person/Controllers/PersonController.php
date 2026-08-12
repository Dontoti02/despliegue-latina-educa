<?php

namespace Modules\Tenant\Packages\Person\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Person\UseCases\PersonUseCases;

class PersonController extends Controller
{
    public function search(Request $request)
    {
        return PersonUseCases::search($request);
    }
}
