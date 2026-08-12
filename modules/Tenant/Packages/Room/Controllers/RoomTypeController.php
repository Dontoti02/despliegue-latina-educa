<?php

namespace Modules\Tenant\Packages\Room\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Room\UseCases\RoomTypeUseCases;

class RoomTypeController extends Controller
{
    public function list(Request $request)
    {
        return RoomTypeUseCases::list($request);
    }

    public function create(Request $request)
    {
        return RoomTypeUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return RoomTypeUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return RoomTypeUseCases::delete($id);
    }
}
