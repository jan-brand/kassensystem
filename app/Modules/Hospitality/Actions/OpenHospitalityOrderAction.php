<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Services\HospitalityOrderNumberGenerator;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class OpenHospitalityOrderAction
{
    public function __construct(
        private readonly HospitalityOrderNumberGenerator $numberGenerator,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(
        DiningTable $table,
        User $actor,
        ?string $note = null,
    ): HospitalityOrder {
        $note = $note === null ? null : trim($note);

        if ($note !== null && mb_strlen($note) > 500) {
            throw new InvalidArgumentException('Die Vorgangsnotiz darf maximal 500 Zeichen enthalten.');
        }

        return DB::transaction(function () use ($table, $actor, $note): HospitalityOrder {
            $lockedTable = DiningTable::query()
                ->with('area')
                ->lockForUpdate()
                ->findOrFail($table->id);

            if (! $lockedTable->active || ! $lockedTable->area->active) {
                throw new LogicException('Der Tisch ist nicht für neue Vorgänge verfügbar.');
            }

            if (
                HospitalityOrder::query()
                    ->where('open_table_id', $lockedTable->id)
                    ->exists()
            ) {
                throw new LogicException('Für diesen Tisch existiert bereits ein offener Vorgang.');
            }

            $order = HospitalityOrder::query()->create([
                'number' => $this->numberGenerator->next(),
                'table_id' => $lockedTable->id,
                'open_table_id' => $lockedTable->id,
                'opened_by_user_id' => $actor->id,
                'status' => HospitalityOrderStatus::Open,
                'note' => $note === '' ? null : $note,
                'total_cents' => 0,
                'opened_at' => now(),
                'closed_at' => null,
            ]);

            $this->audit->execute(
                eventKey: 'hospitality.order.opened',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: HospitalityOrder::class,
                subjectId: $order->id,
                after: [
                    'number' => $order->number,
                    'table_id' => $order->table_id,
                ],
            );

            return $order->load(['table.area', 'openedBy', 'items']);
        });
    }
}
