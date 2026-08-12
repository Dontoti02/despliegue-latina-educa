<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Assistance\Controllers\AssistanceController;

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
    Route::controller(AssistanceController::class)
        ->prefix('assistance')
        ->group(function () {
            Route::get('dates/{classroom_id}', 'dates');
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('mark/{id}', 'mark');
            Route::post('delete', 'deleteMultiple');
        });
});
