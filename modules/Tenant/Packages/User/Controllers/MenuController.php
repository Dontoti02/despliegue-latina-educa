<?php

namespace Modules\Tenant\Packages\User\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\User\UseCases\MenuUseCases;

class MenuController extends Controller
{
    public function get()
    {
        return MenuUseCases::get();
    }
}
