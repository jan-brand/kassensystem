<?php

namespace App\Modules\Preparation\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Services\PreparationDispatchService;
use InvalidArgumentException;
use LogicException;

final class UpdatePreparationStationAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
        private readonly PreparationDispatchService $dispatch,
    ) {}

    public function execute(
        PreparationStation $station,
        string $name,
        PreparationNotificationSound $sound,
        int $sortOrder,
        bool $active,
        ?User $actor = null,
    ): PreparationStation {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Stationsdaten.');
        }

        $station = PreparationStation::query()->findOrFail($station->id);
        $before = $station->only(['name', 'code', 'active', 'sort_order', 'notification_sound']);
        $wasActive = $station->active;

        if ($wasActive && ! $active && $station->tasks()
            ->where('status', '!=', PreparationTaskStatus::Ready->value)
            ->whereHas('order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->exists()) {
            throw new LogicException('Eine Station mit offener Zubereitungsarbeit kann nicht deaktiviert werden.');
        }

        $station->update([
            'name' => $name,
            'notification_sound' => $sound,
            'sort_order' => $sortOrder,
            'active' => $active,
        ]);

        if (! $wasActive && $active) {
            $this->dispatch->backfillStation($station->refresh());
        }

        $this->audit->execute(
            eventKey: 'preparation.station.updated',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: PreparationStation::class,
            subjectId: $station->id,
            before: $before,
            after: $station->fresh()->only(['name', 'code', 'active', 'sort_order', 'notification_sound']),
        );

        return $station->fresh();
    }
}
