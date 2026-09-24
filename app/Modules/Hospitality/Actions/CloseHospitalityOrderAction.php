<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

final class CloseHospitalityOrderAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(HospitalityOrder $order, User $actor): HospitalityOrder
    {
        return DB::transaction(function () use ($order, $actor): HospitalityOrder {
            $locked = HospitalityOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($locked->status === HospitalityOrderStatus::Closed) {
                return $locked->load(['table.area', 'openedBy', 'items.options', 'items.components']);
            }

            $locked->update([
                'status' => HospitalityOrderStatus::Closed,
                'open_table_id' => null,
                'closed_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'hospitality.order.closed',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: HospitalityOrder::class,
                subjectId: $locked->id,
                after: [
                    'number' => $locked->number,
                    'table_id' => $locked->table_id,
                    'total_cents' => $locked->total_cents,
                ],
            );

            return $locked->refresh()->load([
                'table.area',
                'openedBy',
                'items.options',
                'items.components',
            ]);
        });
    }
}
