<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Models\DiningArea;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class CreateDiningAreaAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(string $name, int $sortOrder = 0, ?User $actor = null): DiningArea
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Bereichsdaten.');
        }

        $area = DiningArea::query()->create([
            'name' => $name,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'hospitality.area.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: DiningArea::class,
            subjectId: $area->id,
            after: $area->only(['name', 'active', 'sort_order']),
        );

        return $area;
    }
}
