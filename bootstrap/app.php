<?php

use Modules\Shared\Audit\Middlewares\AuditMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Modules\Shared\Middleware\Cors;
use Modules\Tenant\Middleware\InitializeTenancyByRequestDomain;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        then: function () {
            // para admin
            Route::prefix('api/admin')
            ->middleware('api')
            ->group(base_path('modules/Admin/routes.php'));

            // para tenant
            Route::prefix('api/tenant')
                ->middleware('api')
                ->middleware(InitializeTenancyByRequestDomain::class)
                ->group(base_path('modules/Tenant/routes.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        return 
        $middleware
        ->append(Cors::class)
        ->append(AuditMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();

