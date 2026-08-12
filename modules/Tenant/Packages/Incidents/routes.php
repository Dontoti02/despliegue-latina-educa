<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Incidents\Controllers\IncidentController;

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
    Route::controller(IncidentController::class)
        ->prefix('incident')
        ->group(function () {
            Route::get('check/{incident_number}','check');
            Route::get('params', 'getParams');
            Route::post('create', 'create');
            Route::post('resolve-observation', 'resolveObservation');
            Route::post('download-file', 'downloadFile');
            Route::post('list', 'list');
            Route::get('mark-as-reviewed/{incident_id}', 'markAsReviewed');
            Route::post('new-observation/{incident_id}', 'newObservation');
            Route::post('close-incident', 'closeIncident');
            Route::post('types', 'getIncidentTypes');
            Route::post('types/create', 'createIncidentType');
            Route::put('types/update/{incident_type_id}', 'updateIncidentType');
            Route::delete('types/delete/{incident_type_id}', 'deleteIncidentType');
            Route::post('dashboard','dashboard');
            Route::get('user-incidents','getUserIncidents');
        });
});
