<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Classroom\Controllers\ClassroomController;

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
    Route::controller(ClassroomController::class)
        ->prefix('classroom')
        ->group(function () {
            Route::get('params', 'params');
            Route::post('list', 'list');
            Route::get('detail/{id}', 'detail');
            Route::post('list/courses', 'listCourses');
            Route::post('create', 'create');
            Route::delete('delete/{id}', 'delete');
            Route::post('update/image/{id}', 'updateImage');
            Route::put('update/favorite/{id}', 'updateFavorite');
            Route::post('enroll/{id}/{studentId}', 'enroll');
            Route::post('remove/{id}/{studentId}', 'remove');
            Route::put('toggle/{id}', 'toggle');
        });
});
