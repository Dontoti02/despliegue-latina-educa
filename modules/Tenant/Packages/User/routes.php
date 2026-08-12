<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\User\Controllers\MenuController;
use Modules\Tenant\Packages\User\Controllers\ProfileController;
use Modules\Tenant\Packages\User\Controllers\RolController;
use Modules\Tenant\Packages\User\Controllers\UserController;

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
    Route::controller(UserController::class)
        ->prefix('user')
        ->group(function () {
            Route::get('params', 'params');
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::put('change/password/{id}', 'changePassword');
            Route::post('change/photo/{id}', 'changePhoto');
            Route::delete('delete/photo/{id}', 'deletePhoto');
            Route::put('reset/password/{id}', 'resetPassword');
            Route::put('disable/{id}', 'disable');
            Route::delete('delete/{id}', 'delete');
        });

    Route::controller(RolController::class)
        ->prefix('rol')
        ->group(function () {
            Route::get('list', 'list');
            Route::put('change/{id}', 'change');
        });

    Route::controller(MenuController::class)
        ->prefix('menu')
        ->group(function () {
            Route::get('', 'get');
        });

    Route::controller(ProfileController::class)
        ->prefix('profile')
        ->group(function () {
            Route::get('', 'get');
        });
});
