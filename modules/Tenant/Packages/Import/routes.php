<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Import\Controllers\ImportController;

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
    Route::controller(ImportController::class)
        ->prefix('import')
        ->group(function () {
            Route::get('list', 'list');
            Route::get('get/{id}', 'get');
            Route::get('currently', 'currently');
            Route::get('history/{id}', 'history');
            Route::post('process', 'process');
            Route::post('execute', 'execute');
            Route::post('finish', 'finish');
            Route::post('finish/all', 'finishAll');
        });
});
