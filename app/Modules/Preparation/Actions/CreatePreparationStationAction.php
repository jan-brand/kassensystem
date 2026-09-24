<?php

namespace App\Modules\Preparation\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Models\PreparationStation;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreatePreparationStationAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        string $name,
        string $code,
        PreparationNotificationSound $sound,
        int $sortOrder = 0,
        ?User $actor = null,
    ): PreparationStation {
        $name = trim($name);
        $code = Str::slug(trim($code) !== '' ? $code : $name);

        if ($name === '' || mb_strlen($name) > 120 || $code === '' || mb_strlen($code) > 64 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Stationsdaten.');
        }

        if (PreparationStation::query()->where('code', $code)->exists()) {
            throw new InvalidArgumentException('Dieser Stationscode wird bereits verwendet.');
        }

        $station = PreparationStation::query()->create([
            'name' => $name,
            'code' => $code,
            'active' => true,
            'sort_order' => $sortOrder,
            'notification_sound' => $sound,
        ]);

        $this->audit->execute(
            eventKey: 'preparation.station.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: PreparationStation::class,
            subjectId: $station->id,
            after: $station->only(['name', 'code', 'active', 'sort_order', 'notification_sound']),
        );

        return $station;
    }
}
