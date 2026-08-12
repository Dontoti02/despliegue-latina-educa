<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Cycle\Controllers\CycleController;

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
    Route::controller(CycleController::class)
        ->prefix('cycle')
        ->group(function () {
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::put('sort/{id}/{position}', 'sort');
            Route::delete('delete/{id}', 'delete');
        });
});
