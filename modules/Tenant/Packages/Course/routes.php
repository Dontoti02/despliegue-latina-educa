<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Course\Controllers\CourseController;

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
    Route::controller(CourseController::class)
        ->prefix('course')
        ->group(function () {
            Route::get('params', 'params');
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::put('toggle/{id}', 'toggle');
            Route::delete('delete/{id}', 'delete');
            Route::get('all', 'all');
        });
});
