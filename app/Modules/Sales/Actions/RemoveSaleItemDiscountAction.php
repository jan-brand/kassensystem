<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RemoveSaleItemDiscountAction
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(SaleItem $item, User $actor): Sale
    {
        $this->authorization->authorize($actor, Permission::SalesDiscountsApply);

        return DB::transaction(function () use ($item, $actor): Sale {
            $lockedItem = SaleItem::query()->lockForUpdate()->findOrFail($item->id);
            $sale = Sale::query()->lockForUpdate()->findOrFail($lockedItem->sale_id);

            if ($sale->status !== SaleStatus::Open) {
                throw new LogicException('Rabatte können nur in offenen Verkäufen entfernt werden.');
            }

            $session = CashSession::query()->findOrFail($sale->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            if ($lockedItem->discount_type === null) {
                return $sale->refresh()->load('items');
            }

            $before = [
                'unit_price_cents' => $lockedItem->unit_price_cents,
                'discount_type' => $lockedItem->discount_type->value,
                'discount_value' => $lockedItem->discount_value,
                'discount_source' => $lockedItem->discount_source?->value,
                'discount_label' => $lockedItem->discount_label,
                'discount_offer_id' => $lockedItem->discount_offer_id,
            ];
            $original = $lockedItem->original_unit_price_cents ?? $lockedItem->unit_price_cents;

            $lockedItem->update([
                'original_unit_price_cents' => $original,
                'unit_price_cents' => $original,
                'discount_type' => null,
                'discount_value' => null,
                'discount_source' => null,
                'discount_label' => null,
                'discount_offer_id' => null,
                'total_cents' => $original * $lockedItem->quantity,
            ]);

            $total = (int) $sale->items()->sum('total_cents');
            $sale->update([
                'subtotal_cents' => $total,
                'total_cents' => $total,
            ]);

            $this->audit->execute(
                eventKey: 'sale.item.discount_removed',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: SaleItem::class,
                subjectId: $lockedItem->id,
                before: $before,
                after: [
                    'unit_price_cents' => $original,
                    'discount_type' => null,
                    'discount_value' => null,
                    'discount_source' => null,
                    'discount_label' => null,
                    'discount_offer_id' => null,
                ],
                metadata: ['sale_id' => $sale->id],
            );

            return $sale->refresh()->load('items');
        });
    }
}
