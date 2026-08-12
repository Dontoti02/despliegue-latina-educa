<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\SystemConfiguration\Controllers\SystemConfigurationController;

#rutas publicas
Route::group(['middleware' => [
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    Route::controller(SystemConfigurationController::class)
        ->prefix('system_configuration')
        ->group(function () {
            Route::get('general', 'general');
            Route::get('landing_page', 'landingPage');
        });
});

#rutas privadas
Route::group(['middleware' => [
    AuthTenantMiddleware::class,
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    Route::controller(SystemConfigurationController::class)
        ->prefix('system_configuration')
        ->group(function () {
            Route::get('list', 'list');
            Route::post('update/{key}', 'update');
            Route::post('upload/image', 'uploadImage');
            Route::delete('delete/image', 'deleteImage');
        });
});
