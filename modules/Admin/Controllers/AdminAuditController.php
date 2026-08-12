<?php

namespace Modules\Admin\Controllers;
use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Shared\Audit\Repositories\AuditRepository;
use Modules\Shared\Audit\DTOs\AuditFilterDTO;
use Modules\Shared\Utils\Response;

class AdminAuditController extends Controller
{
    public function __construct(private AuditRepository $auditRepository) {}

    public function index(Request $request): JsonResponse
    {
      try {
          $filters = AuditFilterDTO::fromRequest($request);
          $logs = $this->auditRepository->getLogsForAdmin($filters);
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

    public function dashboard(Request $request): JsonResponse
    {
      try {
          $filters = AuditFilterDTO::fromRequest($request);
          $dashboardData = $this->auditRepository->getDashboardDataForAdmin($filters);
          return Response::success($dashboardData);
      } catch (Exception $e) {
          return Response::error($e->getMessage());
      }
    }

    public function config(): JsonResponse
    {
        try {
            $config = $this->auditRepository->getConfigForAdmin();
            return Response::success($config);
        } catch (Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}