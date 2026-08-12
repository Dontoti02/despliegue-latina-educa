<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\SyllabusManager\Controllers\SyllabusTemplateController;
use Modules\Tenant\Packages\SyllabusManager\Controllers\SyllabusInstanceController;
use Modules\Tenant\Packages\SyllabusManager\Controllers\SyllabusProgressController;

Route::group(['middleware' => [
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    // public routes can be added here if needed
});

Route::group(['middleware' => [
    AuthTenantMiddleware::class,
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    Route::controller(SyllabusTemplateController::class)
        ->prefix('syllabus-templates')
        ->group(function () {
            Route::get('/', 'index');
            Route::get('/create', 'create');
            Route::post('/', 'store');
            Route::get('/{id}/edit', 'edit');
            Route::put('/{id}', 'update');
            Route::get('/{id}', 'show');
        });

    Route::controller(SyllabusProgressController::class)
        ->prefix('syllabus-progress')
        ->group(function () {
            Route::post('list', 'list');
            Route::post('download/{type}', 'download');
        });

    Route::controller(SyllabusInstanceController::class)
        ->prefix('syllabus')
        ->group(function () {
            Route::get('/{classroom_id}', 'showByClassroom');
            Route::get('/templates/{classroom_id}', 'templates');
            Route::post('/', 'store');
            Route::post('/{id}', 'update');
            Route::post('/competency/{competencyId}/change-status', 'changeCompetencyStatus');
            Route::get('/{id}/timeline', 'timeline');
        });
});
