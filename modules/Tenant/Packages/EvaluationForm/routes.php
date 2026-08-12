<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\EvaluationForm\Controllers\EvaluationFormController;

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
    Route::controller(EvaluationFormController::class)
        ->prefix('evaluation_form')
        ->group(function () {
            Route::get('question/types', 'questionTypes');
            Route::get('{uuid}/{person_id?}', 'get');
            Route::post('delivered', 'delivered');
        });
});
