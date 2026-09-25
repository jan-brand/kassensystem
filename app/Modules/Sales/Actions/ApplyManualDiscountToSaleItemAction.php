<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ApplyManualDiscountToSaleItemAction
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
        private readonly DiscountPriceCalculator $calculator,
    ) {}

    public function execute(
        SaleItem $item,
        User $actor,
        DiscountType $type,
        int $value,
    ): Sale {
        $this->authorization->authorize($actor, Permission::SalesDiscountsApply);

        return DB::transaction(function () use ($item, $actor, $type, $value): Sale {
            $lockedItem = SaleItem::query()->lockForUpdate()->findOrFail($item->id);
            $sale = Sale::query()->lockForUpdate()->findOrFail($lockedItem->sale_id);

            if ($sale->status !== SaleStatus::Open) {
                throw new LogicException('Rabatte können nur auf offene Verkäufe angewendet werden.');
            }

            $session = CashSession::query()->findOrFail($sale->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            $original = $lockedItem->original_unit_price_cents ?? $lockedItem->unit_price_cents;

            if ($original === 0) {
                throw new InvalidArgumentException('Ein kostenloser Artikel kann nicht weiter rabattiert werden.');
            }

            $final = $this->calculator->finalUnitPrice($original, $type, $value);

            if ($final >= $original) {
                throw new InvalidArgumentException('Der Rabatt muss den Positionspreis tatsächlich reduzieren.');
            }

            $before = $this->snapshot($lockedItem);

            $lockedItem->update([
                'original_unit_price_cents' => $original,
                'unit_price_cents' => $final,
                'discount_type' => $type,
                'discount_value' => $value,
                'discount_source' => DiscountSource::Manual,
                'discount_label' => 'Manueller Rabatt',
                'discount_offer_id' => null,
                'total_cents' => $final * $lockedItem->quantity,
            ]);

            $this->recalculate($sale);

            $this->audit->execute(
                eventKey: 'sale.item.discount_applied',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: SaleItem::class,
                subjectId: $lockedItem->id,
                before: $before,
                after: $this->snapshot($lockedItem->refresh()),
                metadata: ['sale_id' => $sale->id],
            );

            return $sale->refresh()->load('items');
        });
    }

    private function recalculate(Sale $sale): void
    {
        $total = (int) $sale->items()->sum('total_cents');

        $sale->update([
            'subtotal_cents' => $total,
            'total_cents' => $total,
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(SaleItem $item): array
    {
        return [
            'original_unit_price_cents' => $item->original_unit_price_cents,
            'unit_price_cents' => $item->unit_price_cents,
            'discount_type' => $item->discount_type?->value,
            'discount_value' => $item->discount_value,
            'discount_source' => $item->discount_source?->value,
            'discount_label' => $item->discount_label,
            'discount_offer_id' => $item->discount_offer_id,
            'quantity' => $item->quantity,
            'total_cents' => $item->total_cents,
        ];
    }
}
