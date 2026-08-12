<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Content\Controllers\ContentController;

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
    Route::controller(ContentController::class)
        ->prefix('content')
        ->group(function () {
            Route::get('list/{classroom_id}', 'list');
            Route::get('detail/{id}', 'detail');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::put('update/visibility/{id}', 'updateVisibility');
            Route::put('update/status/{id}', 'updateStatus');
            Route::delete('delete/{id}', 'delete');
        });
});
