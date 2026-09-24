<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Models\DiningArea;
use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;
use LogicException;

final class CreateDiningTableAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        DiningArea $area,
        string $name,
        int $sortOrder = 0,
        ?User $actor = null,
    ): DiningTable {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Tischdaten.');
        }

        if (! $area->active) {
            throw new LogicException('In einem inaktiven Bereich kann kein aktiver Tisch angelegt werden.');
        }

        $table = DiningTable::query()->create([
            'area_id' => $area->id,
            'name' => $name,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'hospitality.table.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: DiningTable::class,
            subjectId: $table->id,
            after: [
                'area_id' => $table->area_id,
                'name' => $table->name,
                'active' => $table->active,
                'sort_order' => $table->sort_order,
            ],
        );

        return $table;
    }
}
