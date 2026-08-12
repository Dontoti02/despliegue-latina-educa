<?php

namespace Modules\Tenant\Packages\Home\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\Home\UseCases\HomeUseCases;

class HomeController extends Controller
{
    public function home()
    {
        return HomeUseCases::home();
    }
}
