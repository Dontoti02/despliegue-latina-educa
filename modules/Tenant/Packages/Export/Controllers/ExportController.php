<?php

namespace Modules\Tenant\Packages\Export\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Export\UseCases\ExportUseCases;

class ExportController extends Controller
{
    public function execute(string $key, string $type, Request $request)
    {
        return ExportUseCases::execute($key,  $type, $request);
    }
}
