<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Room\Controllers\RoomController;
use Modules\Tenant\Packages\Room\Controllers\RoomTypeController;

#rutas publicas
Route::group(['middleware' => [
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    //
});

#rutas privadas
Route::group(['middleware' => [
    AuthTenantMiddleware::class,
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    Route::controller(RoomController::class)
        ->prefix('room')
        ->group(function () {
            Route::get('params', 'params');
            Route::post('list', 'list');
            Route::post('upload/image', 'uploadImage');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
            Route::post('list/reserves', 'listReserves');
            Route::post('add/reserve', 'addReserve');
            Route::delete('delete/reserve/{roomReserveId}', 'deleteReserve');
            Route::get('dashboard', 'dashboard');
        });

    Route::controller(RoomTypeController::class)
        ->prefix('room_type')
        ->group(function () {
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
        });
});
