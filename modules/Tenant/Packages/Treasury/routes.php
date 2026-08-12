<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Treasury\Controllers\DenominationController;
use Modules\Tenant\Packages\Treasury\Controllers\PaymentConceptController;
use Modules\Tenant\Packages\Treasury\Controllers\PaymentsController;
use Modules\Tenant\Packages\Treasury\Controllers\ScalesController;

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
    Route::controller(PaymentConceptController::class)
        ->prefix('payment-concept')
        ->group(function () {
            Route::get('all', 'all');
        });

    Route::prefix('treasury')->group(function () {
        Route::controller(PaymentConceptController::class)
            ->prefix('payment-concepts')
            ->group(function () {
                Route::get('all', 'all');
                Route::post('create', 'create');
                Route::put('update/{id}', 'update');
                Route::delete('delete/{id}', 'delete');
                Route::get('movements/{id}', 'movements');
                Route::put('active/{id}', 'active');
                Route::put('inactive/{id}', 'inactive');
                Route::post('search/concept', 'search');
                Route::get('enrollmentConceptsData', 'enrollmentConceptsData');
                Route::get('history/{id}', 'history');
            });

        Route::controller(PaymentsController::class)
            ->prefix('payments')
            ->group(function () {
                Route::get('get/{person_id}/{is_paid}', 'get');
                Route::post('search/student', 'searchStudent');
                Route::post('create', 'create');
                Route::post('refund', 'refund');
                Route::get('detail/{id}', 'detail');
                Route::post('payNextDetail/{id}', 'payNextDetail');
                Route::post('payEnrollment/{id}', 'payEnrollment');
                Route::get('movementsByConcept/{id}', 'movementsByConcept');
                Route::get('ticket/export/{id}', 'exportTicket');
                Route::get('ticket/export/movement/{id}', 'exportMovementTicket');
            });

        Route::controller(ScalesController::class)
            ->prefix('scales')
            ->group(function () {
                Route::post('create', 'create');
                Route::get('all', 'all');
                Route::put('update/{id}', 'update');
                Route::delete('delete/{id}', 'delete');
                Route::get('enrollments/{id}', 'enrollments');
            });

        Route::controller(DenominationController::class)
            ->prefix('denomination')
            ->group(function () {
                Route::get('all', 'all');
            });
    });
});
