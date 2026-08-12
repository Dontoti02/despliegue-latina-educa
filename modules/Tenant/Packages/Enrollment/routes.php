<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Enrollment\Controllers\EnrollmentController;

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
    Route::controller(EnrollmentController::class)
        ->prefix('enrollment')
        ->group(function () {
            // Listado
            Route::get('filters', 'filters');
            Route::post('list', 'list');
            Route::get('{id}', 'get')->whereNumber('id');
            Route::put('update', 'update');
            Route::delete('delete/{id}', 'delete');
            Route::post('download', 'download');

            // Gestión
            Route::get('validate', 'validate');
            Route::get('family/{documentNumber}', 'family');
            Route::post('detail', 'detail');
            Route::post('list/classrooms', 'listClassrooms');
            Route::post('set', 'set');
        });
});
