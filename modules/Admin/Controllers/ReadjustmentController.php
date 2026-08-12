<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Modules\Admin\UseCases\ReadjustmentUseCases;

class ReadjustmentController extends Controller
{
    public function run()
    {
        return ReadjustmentUseCases::run();
    }

    public function syncMigrationsTableToAllTenants()
    {
        return ReadjustmentUseCases::syncMigrationsTableToAllTenants();
    }
}
