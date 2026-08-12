<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Schedule\Controllers\ScheduleController;

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
    Route::controller(ScheduleController::class)
        ->prefix('schedule')
        ->group(function () {
            Route::get('filters', 'filters');
            Route::post('filters/export', 'filtersByExport');
            Route::post('list', 'list');
            Route::post('list/classrooms', 'listClassrooms');
            Route::post('list/classrooms-existing', 'listClassroomsExisting');
            Route::post('list/teachers', 'listTeachers');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
            Route::post('assign/teacher', 'assignTeacher');
        });
});
