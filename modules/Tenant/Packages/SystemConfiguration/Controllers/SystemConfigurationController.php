<?php

namespace Modules\Tenant\Packages\SystemConfiguration\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\SystemConfiguration\UseCases\SystemConfigurationUseCases;

class SystemConfigurationController extends Controller
{
    public function general()
    {
        return SystemConfigurationUseCases::general();
    }

    public function landingPage()
    {
        return SystemConfigurationUseCases::landingPage();
    }

    public function list()
    {
        return SystemConfigurationUseCases::list();
    }

    public function update(string $key, Request $request)
    {
        return SystemConfigurationUseCases::update($key, $request);
    }

    public function uploadImage(Request $request)
    {
        return SystemConfigurationUseCases::uploadImage($request);
    }

    public function deleteImage(Request $request)
    {
        return SystemConfigurationUseCases::deleteImage($request);
    }
}
