<?php

namespace Modules\Shared\Audit\Middlewares;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Shared\Audit\Jobs\ProcessAuditLogJob;
use Modules\Shared\Helpers\SessionManager;
use Modules\Admin\Models\Domain;
use Modules\Admin\Models\User as AdminUser;
use Modules\Tenant\Models\User as TenantUser;

class AuditMiddleware
{
    public static $subdomainIndex = 0;

    private array $excludedRoutes = [
        'api/tenant/audit-logs/*',
        'api/admin/audit-logs/*',
        'storage/*',
        'api/admin/*'
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('trace_id', (string) \Illuminate\Support\Str::uuid());

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldSkip($request)) {
            return;
        }

        $eventType = $this->resolveEventType($request);

        if (!$eventType) {
            return;
        }

        $institutionId = $this->resolveInstitutionId($request);
        $userData = $this->resolveUserData();
        $responseMessage = $this->resolveResponseMessage($response);

        $payload = $this->sanitizePayload($request->input());

        if ($request->hasFile('*')) {
            $payload['_files_uploaded'] = array_keys($request->allFiles());
        }

        $logData = [
            'institution_id'   => $institutionId,
            'user_id'          => $userData['id'] ?? null,
            'user_email'       => $userData['email'] ?? null,
            'event_type'       => $eventType,
            'method'           => $request->method(),
            'request_url'      => $request->fullUrl(),
            'request_body'     => $payload,
            'status_code'      => $response->getStatusCode(),
            'response_message' => $responseMessage,
            'ip_address'       => $request->ip(),
            'user_agent'       => substr((string) $request->userAgent(), 0, 500),
            'trace_id'         => $request->attributes->get('trace_id'),
            'created_at'       => now(),
        ];

        ProcessAuditLogJob::dispatch($logData);
    }

    private function resolveInstitutionId(Request $request): ?string
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            return (string) tenancy()->tenant?->getTenantKey();
        }
        
        $headerSubdomain = $request->header('X-subdomain');
        if ($headerSubdomain) {
            $subdomain = $this->makeSubdomain($headerSubdomain);
            if ($subdomain) {
                $domainRecord = Domain::where('domain', $subdomain)->first();
                return $domainRecord?->institution?->id;
            }
        }

        return null;
    }

    protected function makeSubdomain(string $hostname): ?string
    {
        $parts = explode('.', $hostname);

        $isLocalhost = count($parts) === 1;
        $isIpAddress = count(array_filter($parts, 'is_numeric')) === count($parts);
        $isACentralDomain = in_array($hostname, (array) config('tenancy.central_domains'), true);
        $notADomain = $isLocalhost || $isIpAddress;
        $thirdPartyDomain = ! \Illuminate\Support\Str::endsWith($hostname, (array) config('tenancy.central_domains'));

        if ($isACentralDomain || $notADomain || $thirdPartyDomain) {
            return null; // CORREGIDO: Retorna null en vez de instanciar una Excepción
        }

        return $parts[static::$subdomainIndex] ?? null;
    }

    private function resolveUserData(): array
    {
        $session = SessionManager::get();

        if (!isset($session->id)) {
            return ['id' => null, 'email' => null];
        }

        try {
            $isTenantInitialized = function_exists('tenancy') && tenancy()->initialized;
            
            $userModel = $isTenantInitialized 
                ? TenantUser::find($session->id) 
                : AdminUser::find($session->id);

            return [
                'id'    => $userModel?->id ?? $session->id ?? null,
                'email' => $userModel?->email ?? $session->email ?? null,
            ];
        } catch (Exception $e) {
            return [
                'id'    => $session->id ?? null,
                'email' => $session->email ?? null,
            ];
        }
    }

    private function resolveResponseMessage(Response $response): ?string
    {
        $contentType = $response->headers->get('Content-Type', '');

        if (!str_contains($contentType, 'application/json')) {
            return null;
        }

        $content = json_decode($response->getContent(), true);

        if (is_array($content)) {
            return $content['message'] ?? $content['error'] ?? null;
        }

        return null;
    }

    private function resolveEventType(Request $request): ?string
    {
        $method = $request->method();

        if ($request->is('*/download*') || $request->is('*/export*') || $request->has('download')) {
            return 'DESCARGA';
        }

        return match ($method) {
            'GET'          => $request->is('*/export*') ? 'DESCARGA' : 'CONSULTA',
            'POST'         => 'REGISTRO',
            'PUT', 'PATCH' => 'ACTUALIZACION',
            'DELETE'       => 'ELIMINACION',
            default        => null,
        };
    }

    private function sanitizePayload(array $payload): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 
            'secret', 'credit_card', 'cvv', 'authorization'
        ];

        array_walk_recursive($payload, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $value = '********';
            }
        });

        return $payload;
    }

    private function shouldSkip(Request $request): bool
    {
        foreach ($this->excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}