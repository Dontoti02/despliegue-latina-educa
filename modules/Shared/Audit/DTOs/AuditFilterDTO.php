<?php

namespace Modules\Shared\Audit\DTOs;

use Illuminate\Http\Request;

class AuditFilterDTO
{
    public function __construct(
        public ?string $institutionId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $eventType = null,
        public ?int $userId = null,
        public ?string $search = null,
        public int $perPage = 25
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            institutionId: $request->input('institution_id'),
            dateFrom:      $request->input('date_from'),
            dateTo:        $request->input('date_to'),
            eventType:     $request->input('eventType'),
            userId:        $request->input('user_id') ? (int) $request->input('user_id') : null,
            search:        $request->input('search'),
            perPage:       (int) $request->input('perPage', 25)
        );
    }
}