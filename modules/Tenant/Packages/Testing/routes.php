<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Testing\TestingController;

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
    Route::controller(TestingController::class)
        ->prefix('testing')
        ->group(function () {
            Route::get('', 'test');
        });
});
