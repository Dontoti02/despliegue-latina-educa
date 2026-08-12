<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Admin\Models\Domain;
use Modules\Admin\Models\SystemConfiguration;
use Modules\Shared\Utils\Response;

class DomainTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $header = $request->header('x-subdomain');

            $parts = explode('.', $header);

            $domain = implode('.', array_slice($parts, -3));
            $subdomain = implode('.', array_slice($parts, 0, count($parts) - 3));

            $parameterDomain = SystemConfiguration::where('key', 'domain')->first();

            if (!$parameterDomain) {
                throw new Exception('No puedes acceder desde este dominio');
            }

            $adminDomain = $parameterDomain->value;

            if ($domain != $adminDomain) {
                throw new Exception('No puedes acceder desde este dominio');
            }

            $tenantId = tenant('id');
            $rowSubdomain = Domain::where('tenant_id', $tenantId)->first();

            if (!$rowSubdomain) {
                throw new Exception('Tu institución no se encuentra registrada');
            }

            $adminSubdomain = $rowSubdomain->domain;

            if ($subdomain != $adminSubdomain) {
                throw new Exception('Tu institución no se encuentra registrada');
            }

            return $next($request);
        } catch (Exception $e) {
            return Response::notFound($e->getMessage());
        }
    }
}
