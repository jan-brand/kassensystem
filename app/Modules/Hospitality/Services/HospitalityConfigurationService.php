<?php

namespace App\Modules\Hospitality\Services;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Models\DiningArea;
use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class HospitalityConfigurationService
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
        private readonly CreateDiningAreaAction $createArea,
        private readonly CreateDiningTableAction $createTable,
    ) {}

    public function createArea(User $actor, string $name, int $sortOrder = 0): DiningArea
    {
        $this->authorize($actor);
        $this->assertAreaNameAvailable($name);

        return $this->createArea->execute($name, $sortOrder, $actor);
    }

    public function createTable(
        User $actor,
        DiningArea $area,
        string $name,
        int $sortOrder = 0,
    ): DiningTable {
        $this->authorize($actor);
        $this->assertTableNameAvailable($area, $name);

        return $this->createTable->execute($area, $name, $sortOrder, $actor);
    }

    public function updateArea(
        User $actor,
        DiningArea $area,
        string $name,
        int $sortOrder,
    ): DiningArea {
        $this->authorize($actor);
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Bereichsdaten.');
        }

        $this->assertAreaNameAvailable($name, $area->id);

        return DB::transaction(function () use ($actor, $area, $name, $sortOrder): DiningArea {
            $locked = DiningArea::query()->lockForUpdate()->findOrFail($area->id);
            $before = $locked->only(['name', 'active', 'sort_order']);

            $locked->update([
                'name' => $name,
                'sort_order' => $sortOrder,
            ]);

            $this->audit->execute(
                eventKey: 'hospitality.area.updated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiningArea::class,
                subjectId: $locked->id,
                before: $before,
                after: $locked->fresh()->only(['name', 'active', 'sort_order']),
            );

            return $locked->refresh();
        });
    }

    public function updateTable(
        User $actor,
        DiningTable $table,
        DiningArea $area,
        string $name,
        int $sortOrder,
    ): DiningTable {
        $this->authorize($actor);
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 120 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Tischdaten.');
        }

        if (! $area->active) {
            throw new LogicException('Ein Tisch kann keinem inaktiven Bereich zugeordnet werden.');
        }

        $this->assertTableNameAvailable($area, $name, $table->id);

        return DB::transaction(function () use ($actor, $table, $area, $name, $sortOrder): DiningTable {
            $locked = DiningTable::query()
                ->with('openOrder')
                ->lockForUpdate()
                ->findOrFail($table->id);

            if ($locked->area_id !== $area->id && $locked->openOrder !== null) {
                throw new LogicException('Ein Tisch mit offenem Vorgang kann den Bereich nicht wechseln.');
            }

            $before = $locked->only(['area_id', 'name', 'active', 'sort_order']);

            $locked->update([
                'area_id' => $area->id,
                'name' => $name,
                'sort_order' => $sortOrder,
            ]);

            $this->audit->execute(
                eventKey: 'hospitality.table.updated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiningTable::class,
                subjectId: $locked->id,
                before: $before,
                after: $locked->fresh()->only(['area_id', 'name', 'active', 'sort_order']),
            );

            return $locked->refresh();
        });
    }

    public function setAreaActive(User $actor, DiningArea $area, bool $active): DiningArea
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $area, $active): DiningArea {
            $locked = DiningArea::query()->lockForUpdate()->findOrFail($area->id);

            if (
                ! $active
                && DiningTable::query()
                    ->where('area_id', $locked->id)
                    ->whereHas('openOrder')
                    ->exists()
            ) {
                throw new LogicException('Ein Bereich mit offenem Vorgang kann nicht deaktiviert werden.');
            }

            $before = ['active' => $locked->active];
            $locked->update(['active' => $active]);

            $this->audit->execute(
                eventKey: $active ? 'hospitality.area.activated' : 'hospitality.area.deactivated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiningArea::class,
                subjectId: $locked->id,
                before: $before,
                after: ['active' => $locked->active],
            );

            return $locked->refresh();
        });
    }

    public function setTableActive(User $actor, DiningTable $table, bool $active): DiningTable
    {
        $this->authorize($actor);

        return DB::transaction(function () use ($actor, $table, $active): DiningTable {
            $locked = DiningTable::query()
                ->with(['area', 'openOrder'])
                ->lockForUpdate()
                ->findOrFail($table->id);

            if ($active && ! $locked->area->active) {
                throw new LogicException('Ein Tisch in einem inaktiven Bereich kann nicht aktiviert werden.');
            }

            if (! $active && $locked->openOrder !== null) {
                throw new LogicException('Ein Tisch mit offenem Vorgang kann nicht deaktiviert werden.');
            }

            $before = ['active' => $locked->active];
            $locked->update(['active' => $active]);

            $this->audit->execute(
                eventKey: $active ? 'hospitality.table.activated' : 'hospitality.table.deactivated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiningTable::class,
                subjectId: $locked->id,
                before: $before,
                after: ['active' => $locked->active],
            );

            return $locked->refresh();
        });
    }

    private function authorize(User $actor): void
    {
        $this->authorization->authorize($actor, Permission::HospitalityConfigurationManage);
    }

    private function assertAreaNameAvailable(string $name, ?int $exceptId = null): void
    {
        $name = trim($name);

        $query = DiningArea::query()->where('name', $name);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Dieser Bereichsname ist bereits vergeben.');
        }
    }

    private function assertTableNameAvailable(
        DiningArea $area,
        string $name,
        ?int $exceptId = null,
    ): void {
        $name = trim($name);

        $query = DiningTable::query()
            ->where('area_id', $area->id)
            ->where('name', $name);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Dieser Tischname ist in dem Bereich bereits vergeben.');
        }
    }
}
