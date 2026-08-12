<?php

namespace Modules\Tenant\Packages\Comment\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Comment\UseCases\CommentUseCases;

class CommentController extends Controller
{
    public function create(Request $request)
    {
        return CommentUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return CommentUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return CommentUseCases::delete($id);
    }
}
