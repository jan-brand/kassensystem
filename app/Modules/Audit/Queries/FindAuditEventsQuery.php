<?php

namespace App\Modules\Audit\Queries;

use App\Modules\Audit\Models\AuditEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class FindAuditEventsQuery
{
    public function execute(
        ?string $eventKey = null,
        ?string $subjectType = null,
        ?int $actorUserId = null,
        int $perPage = 50,
    ): LengthAwarePaginator {
        return AuditEvent::query()
            ->when($eventKey, fn ($query, $value) => $query->where('event_key', $value))
            ->when($subjectType, fn ($query, $value) => $query->where('subject_type', $value))
            ->when($actorUserId, fn ($query, $value) => $query->where('actor_user_id', $value))
            ->latest('id')
            ->paginate($perPage);
    }
}
