<?php

namespace Modules\Shared\Audit\Repositories;

use Modules\Shared\Audit\Models\AuditLog;
use Modules\Shared\Audit\DTOs\AuditFilterDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditRepository
{
    public function getConfigForAdmin(): array
    {
        return [
            'eventTypes' => AuditLog::select('event_type')
                ->distinct()
                ->pluck('event_type')
                ->toArray(),
            'institutions' => AuditLog::with('institution')
              ->select('institution_id')
              ->whereNotNull('institution_id')
              ->distinct()
              ->get()
              ->filter(fn ($log) => $log->institution)
              ->map(fn ($log) => [
                  'id'   => $log->institution->id,
                  'name' => $log->institution->name,
              ])
              ->values()
              ->toArray(),
        ];
    }

    public function getDashboardDataForAdmin(AuditFilterDTO $filters): array
    {
        $query = AuditLog::query();

        if ($filters->dateFrom && $filters->dateTo) {
            $query->whereBetween('created_at', [$filters->dateFrom, $filters->dateTo]);
        } elseif ($filters->dateFrom) {
            $query->where('created_at', '>=', $filters->dateFrom);
        } elseif ($filters->dateTo) {
            $query->where('created_at', '<=', $filters->dateTo);
        }

        if ($filters->eventType) {
            $query->where('event_type', $filters->eventType);
        }

        if ($filters->userId) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->search) {
            $query->where(function ($q) use ($filters) {
                $q->where('request_url', 'like', "%{$filters->search}%")
                  ->orWhere('user_email', 'like', "%{$filters->search}%")
                  ->orWhere('response_message', 'like', "%{$filters->search}%")
                  ->orWhere('request_body', 'like', "%{$filters->search}%");
            });
        }

        $baseQuery = clone $query;
        
        $topEventsData = $baseQuery
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $topEvents = $topEventsData->map(function ($item) {
            return [
                'name' => $item->event_type,
                'count' => $item->count,
            ];
        })->toArray();

        return [
            'totalEvents' => $query->count(),
            'successCount' => $query->clone()->where('status_code', '>=', 200)->where('status_code', '<', 300)->count(),
            'errorCount' => $query->clone()->where('status_code', '>=', 400)->count(),
            'uniqueUsers' => $query->clone()->distinct('user_id')->count('user_id'),
            'uniqueInstitutions' => $query->clone()->distinct('institution_id')->count('institution_id'),
            'recentEvents' => $query->clone()->where('created_at', '>=', now()->subHours(24))->count(),
            'topEvents' => $topEvents,
        ];
    }

    public function getLogsForTenant(string $tenantId, AuditFilterDTO $filters): LengthAwarePaginator
    {
        $filters->institutionId = $tenantId;
        return $this->getPaginatedLogs($filters);
    }

    public function getLogsForAdmin(AuditFilterDTO $filters): LengthAwarePaginator
    {
        return $this->getPaginatedLogs($filters);
    }

    private function getPaginatedLogs(AuditFilterDTO $filters): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if ($filters->institutionId) {
            $query->where('institution_id', $filters->institutionId);
        }

        if ($filters->dateFrom && $filters->dateTo) {
            $query->whereBetween('created_at', [$filters->dateFrom, $filters->dateTo]);
        } elseif ($filters->dateFrom) {
            $query->where('created_at', '>=', $filters->dateFrom);
        } elseif ($filters->dateTo) {
            $query->where('created_at', '<=', $filters->dateTo);
        }

        if ($filters->eventType) {
            $query->where('event_type', $filters->eventType);
        }

        if ($filters->userId) {
            $query->where('user_id', $filters->userId);
        }

        if ($filters->search) {
            $query->where(function ($q) use ($filters) {
                $q->where('request_url', 'like', "%{$filters->search}%")
                  ->orWhere('user_email', 'like', "%{$filters->search}%")
                  ->orWhere('response_message', 'like', "%{$filters->search}%")
                  ->orWhere('request_body', 'like', "%{$filters->search}%");
            });
        }

        return $query->orderBy('id', 'desc')->paginate($filters->perPage);
    }
}