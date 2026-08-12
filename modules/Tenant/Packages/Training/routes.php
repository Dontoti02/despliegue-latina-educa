<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Middleware\AuthTenantMiddleware;
use Modules\Tenant\Middleware\DomainTenantMiddleware;
use Modules\Tenant\Middleware\SubscriptionTenantMiddleware;
use Modules\Tenant\Packages\Training\Controllers\TrainingAnswerController;
use Modules\Tenant\Packages\Training\Controllers\TrainingAssistanceController;
use Modules\Tenant\Packages\Training\Controllers\TrainingCommentController;
use Modules\Tenant\Packages\Training\Controllers\TrainingContentController;
use Modules\Tenant\Packages\Training\Controllers\TrainingContentGroupController;
use Modules\Tenant\Packages\Training\Controllers\TrainingController;
use Modules\Tenant\Packages\Training\Controllers\TrainingEvaluationController;
use Modules\Tenant\Packages\Training\Controllers\TrainingEvaluationFormController;
use Modules\Tenant\Packages\Training\Controllers\TrainingEvaluationGroupController;
use Modules\Tenant\Packages\Training\Controllers\TrainingParticipantController;
use Modules\Tenant\Packages\Training\Controllers\TrainingPublicationController;
use Modules\Tenant\Packages\Training\Controllers\TrainingReportController;
use Modules\Tenant\Packages\Training\Controllers\TrainingTaskGroupController;

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
    Route::controller(TrainingController::class)
        ->prefix('training')
        ->group(function () {
            Route::get('filters', 'filters');
            Route::post('list', 'list');
            Route::get('{id}', 'get');
            Route::post('set', 'set');
            Route::delete('delete/{id}', 'delete');
            Route::post('update/image', 'updateImage');
            Route::delete('delete/image/{id}', 'deleteImage');
            Route::put('update/favorite/{id}', 'updateFavorite');
            Route::get('find/person', 'findPerson');
            Route::post('create/person', 'createPerson');
            Route::post('assign/teacher', 'assignTeacher');
            Route::get('filters/students', 'filtersStudents');
            Route::post('list/students', 'listStudents');
            Route::post('list/students/download', 'listStudentsDownload');
            Route::post('assign/student', 'assignStudent');
            Route::put('unassign/student', 'unassignStudent');
            Route::put('activate/student', 'activateStudent');
            Route::post('certificate/download', 'certificateDownload');
            Route::get('certificate/download/zip/{id}', 'certificateDownloadZip');
            Route::post('create-category', 'createCategory');
        });

    Route::controller(TrainingReportController::class)
        ->prefix('training/report')
        ->group(function () {
            Route::get('filters', 'filters');
            Route::post('list', 'list');
            Route::post('list/download', 'listDownload');
        });

    Route::controller(TrainingParticipantController::class)
        ->prefix('training/participant')
        ->group(function () {
            Route::get('list/{training_id}', 'list');
        });

    Route::controller(TrainingEvaluationGroupController::class)
        ->prefix('training/evaluation-group')
        ->group(function () {
            Route::get('list/{training_id}', 'list');
            Route::post('set', 'set');
        });

    Route::controller(TrainingContentGroupController::class)
        ->prefix('training/content-group')
        ->group(function () {
            Route::get('list/{training_id}', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
        });

    Route::controller(TrainingContentController::class)
        ->prefix('training/content')
        ->group(function () {
            Route::get('list/{training_id}', 'list');
            Route::get('detail/{id}', 'detail');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::put('update/visibility/{id}', 'updateVisibility');
            Route::put('update/status/{id}', 'updateStatus');
            Route::delete('delete/{id}', 'delete');
        });

    Route::controller(TrainingTaskGroupController::class)
        ->prefix('training/content/task_group')
        ->group(function () {
            Route::get('list/{training_content_id}', 'list');
            Route::post('set', 'set');
            Route::delete('delete/{id}', 'delete');
            Route::get('participant/list/{training_id}', 'listParticipants');
            Route::post('participant/set', 'setParticipant');
            Route::delete('participant/delete/{id}', 'deleteParticipant');
        });

    Route::controller(TrainingPublicationController::class)
        ->prefix('training/publication')
        ->group(function () {
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
        });

    Route::controller(TrainingCommentController::class)
        ->prefix('training/comment')
        ->group(function () {
            Route::post('create', 'create');
            Route::put('update/{id}', 'update');
            Route::delete('delete/{id}', 'delete');
        });

    Route::controller(TrainingAnswerController::class)
        ->prefix('training/answer')
        ->group(function () {
            Route::get('list/{training_content_id}', 'list');
            Route::put('delivered/{id}', 'delivered');
        });

    Route::controller(TrainingAssistanceController::class)
        ->prefix('training/assistance')
        ->group(function () {
            Route::get('dates/{training_id}', 'dates');
            Route::post('list', 'list');
            Route::post('create', 'create');
            Route::put('mark/{id}', 'mark');
            Route::post('delete', 'deleteMultiple');
        });

    Route::controller(TrainingEvaluationController::class)
        ->prefix('training/evaluation')
        ->group(function () {
            Route::get('list/{training_id}/{person_id?}', 'list');
            Route::put('evaluate', 'evaluate');
        });

    Route::controller(TrainingEvaluationFormController::class)
        ->prefix('training/evaluation_form')
        ->group(function () {
            Route::get('question/types', 'questionTypes');
            Route::get('{uuid}/{person_id?}', 'get');
            Route::post('delivered', 'delivered');
        });
});
