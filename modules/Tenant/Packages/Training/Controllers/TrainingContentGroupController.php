<?php

namespace Modules\Tenant\Packages\Training\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Training\UseCases\TrainingContentGroupUseCases;

class TrainingContentGroupController extends Controller
{
    public function list(int $training_id)
    {
        return TrainingContentGroupUseCases::list($training_id);
    }

    public function create(Request $request)
    {
        return TrainingContentGroupUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return TrainingContentGroupUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return TrainingContentGroupUseCases::delete($id);
    }
}
