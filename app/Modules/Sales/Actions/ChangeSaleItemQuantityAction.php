<?php

namespace App\Modules\Sales\Actions;

use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class ChangeSaleItemQuantityAction
{
    public function execute(SaleItem $item, int $quantity): Sale
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Quantity must not be negative.');
        }

        return DB::transaction(function () use ($item, $quantity): Sale {
            $lockedItem = SaleItem::query()->lockForUpdate()->findOrFail($item->id);
            $sale = Sale::query()->lockForUpdate()->findOrFail($lockedItem->sale_id);

            if ($sale->status !== SaleStatus::Open) {
                throw new LogicException('Only an open sale can be changed.');
            }

            $session = CashSession::query()->findOrFail($sale->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            if ($quantity === 0) {
                $lockedItem->delete();
            } else {
                $lockedItem->update([
                    'quantity' => $quantity,
                    'total_cents' => $quantity * $lockedItem->unit_price_cents,
                ]);
            }

            $total = (int) $sale->items()->sum('total_cents');

            $sale->update([
                'subtotal_cents' => $total,
                'total_cents' => $total,
            ]);

            return $sale->refresh()->load('items');
        });
    }
}
