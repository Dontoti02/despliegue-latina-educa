<?php

namespace Modules\Tenant\Packages\Audit\Controllers;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Audit\Repositories\AuditRepository;
use Modules\Shared\Audit\DTOs\AuditFilterDTO;
use Modules\Shared\Utils\Response;

class TenantAuditController extends Controller
{
    public function __construct(private AuditRepository $auditRepository) {}

    public function index(Request $request): JsonResponse
    {
      try {
        $tenantId = tenant('id');
        $filters = AuditFilterDTO::fromRequest($request);
        $logs = $this->auditRepository->getLogsForTenant($tenantId, $filters);
        $data = [
              'items'  => $logs->items(),
              'meta'   => [
                  'current_page' => $logs->currentPage(),
                  'last_page'    => $logs->lastPage(),
                  'per_page'     => $logs->perPage(),
                  'total'        => $logs->total(),
              ]
          ];
        return Response::success($data);
      } catch (Exception $e) {
          return Response::error($e->getMessage());
      }
    }
}