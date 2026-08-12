<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\QuestionBank\Controllers\QuestionBankController;
use Modules\Tenant\Packages\QuestionBank\Controllers\QuestionTaxonomyController;

/**
 * Banco de Preguntas.
 *
 * Todas las rutas son privadas y verifican rol dentro del repositorio
 * (QuestionBankHelper). No existe variante pública: las opciones incluyen
 * `is_correct`, es decir, el solucionario de las evaluaciones.
 */
Route::group(['middleware' => [
    AuthTenantMiddleware::class,
    DomainTenantMiddleware::class,
    SubscriptionTenantMiddleware::class,
]], function () {
    Route::controller(QuestionBankController::class)
        ->prefix('question-bank')
        ->group(function () {
            Route::get('params', 'params');
            Route::post('list', 'list');
            Route::post('pick', 'pick');
            Route::post('/', 'store');
            Route::get('{id}', 'show')->whereNumber('id');
            Route::put('{id}', 'update')->whereNumber('id');
            Route::delete('{id}', 'destroy')->whereNumber('id');
        });

    Route::controller(QuestionTaxonomyController::class)
        ->prefix('question-bank/taxonomy')
        ->group(function () {
            Route::post('subjects/list', 'listSubjects');
            Route::post('subjects', 'storeSubject');
            Route::put('subjects/{id}', 'updateSubject')->whereNumber('id');
            Route::delete('subjects/{id}', 'destroySubject')->whereNumber('id');

            Route::post('topics/list', 'listTopics');
            Route::post('topics', 'storeTopic');
            Route::put('topics/{id}', 'updateTopic')->whereNumber('id');
            Route::delete('topics/{id}', 'destroyTopic')->whereNumber('id');
        });
});
