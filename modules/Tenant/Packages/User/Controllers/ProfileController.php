<?php

namespace Modules\Tenant\Packages\User\Controllers;

use App\Http\Controllers\Controller;
use Modules\Tenant\Packages\User\UseCases\ProfileUseCases;

class ProfileController extends Controller
{
    public function get()
    {
        return ProfileUseCases::get();
    }
}
