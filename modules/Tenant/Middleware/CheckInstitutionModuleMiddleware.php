<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Modules\Admin\Models\Domain;
use Modules\Admin\Models\InstitutionModule;
use Modules\Shared\Services\CentralTransactionService;
use Modules\Shared\Utils\Response;

class CheckInstitutionModuleMiddleware
{
  public function handle(Request $request, Closure $next, $moduleKey)
  {
    try {
      $tenantId = tenant('id');
      $domain = Domain::byKey('tenant_id', $tenantId);
      $institution = $domain->institution;

      $module = CentralTransactionService::run(function () use ($institution, $moduleKey) {
        return InstitutionModule::where('institution_id', $institution->id)
          ->where('module_key', $moduleKey)
          ->first();
      });

      if (
        !$module ||
        !$module->is_active ||
        ($module->start_date && Carbon::now()->lt(Carbon::parse($module->start_date)->startOfDay())) ||
        ($module->end_date && Carbon::now()->gt(Carbon::parse($module->end_date)->endOfDay()))
      ) {
        throw new Exception('Módulo no habilitado o fuera de vigencia.');
      }

      return $next($request);
    } catch (Exception $e) {
      return Response::forbidden($e->getMessage());
    }
  }
}
