<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use LogicException;

final class DiscardOpenSaleAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Sale $sale, User $actor): void
    {
        DB::transaction(function () use ($sale, $actor): void {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status !== SaleStatus::Open) {
                throw new LogicException('Only an open sale can be discarded.');
            }

            $metadata = [
                'register_id' => $locked->register_id,
                'cash_session_id' => $locked->cash_session_id,
                'total_cents' => $locked->total_cents,
            ];

            $locked->items()->delete();
            $locked->delete();

            $this->audit->execute(
                eventKey: 'sale.cart_discarded',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                metadata: $metadata,
            );
        });
    }
}
