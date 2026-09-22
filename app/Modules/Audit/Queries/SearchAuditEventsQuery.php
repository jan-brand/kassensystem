<?php

namespace App\Modules\Audit\Queries;

use App\Modules\Audit\Models\AuditEvent;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class SearchAuditEventsQuery
{
    /** @return LengthAwarePaginator<AuditEvent> */
    public function execute(
        ?string $eventKey = null,
        ?string $actor = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $eventKey = $this->clean($eventKey);
        $actor = $this->clean($actor);
        $subjectType = $this->clean($subjectType);

        return AuditEvent::query()
            ->when(
                $eventKey,
                fn (Builder $query, string $value): Builder => $query->where('event_key', 'like', "%{$value}%"),
            )
            ->when(
                $actor,
                function (Builder $query, string $value): Builder {
                    return $query->where(function (Builder $actorQuery) use ($value): void {
                        $actorQuery
                            ->where('actor_username', 'like', "%{$value}%")
                            ->orWhere('actor_display_name', 'like', "%{$value}%");

                        if (ctype_digit($value)) {
                            $actorQuery->orWhere('actor_user_id', (int) $value);
                        }
                    });
                },
            )
            ->when(
                $subjectType,
                fn (Builder $query, string $value): Builder => $query->where('subject_type', 'like', "%{$value}%"),
            )
            ->when(
                $subjectId,
                fn (Builder $query, int $value): Builder => $query->where('subject_id', $value),
            )
            ->when(
                $from,
                fn (Builder $query, CarbonInterface $value): Builder => $query->where('created_at', '>=', $value),
            )
            ->when(
                $to,
                fn (Builder $query, CarbonInterface $value): Builder => $query->where('created_at', '<=', $value),
            )
            ->latest('id')
            ->paginate($perPage);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
