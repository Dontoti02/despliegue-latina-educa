<?php

namespace Modules\Tenant\Middleware;

use Carbon\Carbon;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Admin\Models\Domain;
use Modules\Shared\Utils\Response;

class SubscriptionTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $tenantId = tenant('id');
            $domain = Domain::byKey('tenant_id', $tenantId);
            $institution = $domain->institution;

            $now = Carbon::now();

            $start_date = Carbon::parse($institution->start_date);

            if ($now < $start_date) {
                throw new Exception('Su suscripción aun no ha iniciado');
            }

            $is_active = true;

            if ($institution->end_date) {
                $end_date = Carbon::parse($institution->end_date);

                $is_active = $now >= $start_date && $now <= $end_date;

                if ($is_active != $institution->is_active) {
                    $institution->update([
                        'is_active' => $is_active,
                    ]);
                }

                if ($now > $end_date) {
                    throw new Exception('Su suscripción ha finalizado');
                }
            }

            if ($is_active != $institution->is_active) {
                $institution->update([
                    'is_active' => $is_active,
                ]);
            }

            return $next($request);
        } catch (Exception $e) {
            return Response::noSubscription($e->getMessage());
        }
    }
}
