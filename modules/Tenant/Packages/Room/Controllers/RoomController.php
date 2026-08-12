<?php

namespace Modules\Tenant\Packages\Room\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Tenant\Packages\Room\UseCases\RoomUseCases;

class RoomController extends Controller
{
    public function params()
    {
        return RoomUseCases::params();
    }

    public function list(Request $request)
    {
        return RoomUseCases::list($request);
    }

    public function uploadImage(Request $request)
    {
        return RoomUseCases::uploadImage($request);
    }

    public function create(Request $request)
    {
        return RoomUseCases::create($request);
    }

    public function update(int $id, Request $request)
    {
        return RoomUseCases::update($id, $request);
    }

    public function delete(int $id)
    {
        return RoomUseCases::delete($id);
    }

    public function listReserves(Request $request)
    {
        return RoomUseCases::listReserves($request);
    }

    public function addReserve(Request $request)
    {
        return RoomUseCases::addReserve($request);
    }

    public function deleteReserve(int $roomReserveId)
    {
        return RoomUseCases::deleteReserve($roomReserveId);
    }

    public function dashboard(Request $request)
    {
        return RoomUseCases::dashboard($request);
    }
}
