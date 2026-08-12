<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\StudyPlanDetail\Controllers\StudyPlanDetailController;

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
    Route::controller(StudyPlanDetailController::class)
        ->prefix('study_plan_detail')
        ->group(function () {
            Route::get('detail/{studyPlanId}', 'detail');
            Route::get('params/{studyPlanId}', 'params');
            Route::post('assign', 'assign');
            Route::put('unassign/{id}', 'unassign');
        });
});
