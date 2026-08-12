<?php

namespace Modules\Tenant\Packages\File\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\File\UseCases\FileUseCases;

class FileController extends Controller
{
    public function download(string $uuid)
    {
        return FileUseCases::download($uuid);
    }

    public function upload(Request $request)
    {
        return FileUseCases::upload($request);
    }

    public function delete(int $id, string $model)
    {
        return FileUseCases::delete($id, $model);
    }
}
