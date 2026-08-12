<?php

namespace Modules\Shared\Services;
use Modules\Admin\Models\Institution;
class CentralTransactionService
{
    public static function institution($tenantId): ?Institution
    {
        return Institution::on('central')
            ->whereHas('domain', function($subquery) use ($tenantId) {
                return $subquery->where('tenant_id', $tenantId);
            })
            ->with('storage')
            ->first();
    }
}
