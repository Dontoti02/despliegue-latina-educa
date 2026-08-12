<?php

namespace Modules\Tenant\Packages\Import\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Import\UseCases\ImportUseCases;

class ImportController extends Controller
{
    public function list()
    {
        return ImportUseCases::list();
    }

    public function get(int $id)
    {
        return ImportUseCases::get($id);
    }

    public function currently()
    {
        return ImportUseCases::currently();
    }

    public function history(int $id)
    {
        return ImportUseCases::history($id);
    }

    public function process(Request $request)
    {
        return ImportUseCases::process($request);
    }

    public function execute(Request $request)
    {
        return ImportUseCases::execute($request);
    }

    public function finish(Request $request)
    {
        return ImportUseCases::finish($request);
    }

    public function finishAll()
    {
        return ImportUseCases::finishAll();
    }
}
