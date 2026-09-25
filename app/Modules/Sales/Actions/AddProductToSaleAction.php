<?php

namespace App\Modules\Sales\Actions;

use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Queries\GetActiveDiscountOfferForProductQuery;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AddProductToSaleAction
{
    public function __construct(
        private readonly GetActiveDiscountOfferForProductQuery $activeOffer,
        private readonly DiscountPriceCalculator $calculator,
    ) {}

    public function execute(Sale $sale, Product $product): Sale
    {
        return DB::transaction(function () use ($sale, $product): Sale {
            $locked = Sale::query()->lockForUpdate()->findOrFail($sale->id);

            if ($locked->status !== SaleStatus::Open) {
                throw new LogicException('Only an open sale can be changed.');
            }

            $session = CashSession::query()->findOrFail($locked->cash_session_id);

            if ($session->status !== CashSessionStatus::Open) {
                throw new LogicException('Cash session is not open.');
            }

            $product = Product::query()->with('category')->findOrFail($product->id);

            if (! $product->active || ! $product->category->active) {
                throw new LogicException('Product is not available for sale.');
            }

            $item = SaleItem::query()
                ->where('sale_id', $locked->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item === null) {
                $original = $product->price_cents;
                $unitPrice = $original;
                $offer = $this->activeOffer->execute($product);

                if ($offer !== null) {
                    $unitPrice = $this->calculator->finalUnitPrice($original, $offer->type, $offer->value);
                }

                SaleItem::query()->create([
                    'sale_id' => $locked->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'original_unit_price_cents' => $original,
                    'unit_price_cents' => $unitPrice,
                    'discount_type' => $offer?->type,
                    'discount_value' => $offer?->value,
                    'discount_source' => $offer !== null ? DiscountSource::Offer : null,
                    'discount_label' => $offer?->name,
                    'discount_offer_id' => $offer?->id,
                    'is_consumable' => $product->is_consumable,
                    'quantity' => 1,
                    'total_cents' => $unitPrice,
                ]);
            } else {
                $quantity = $item->quantity + 1;

                $item->update([
                    'quantity' => $quantity,
                    'total_cents' => $quantity * $item->unit_price_cents,
                ]);
            }

            $this->recalculate($locked);

            return $locked->refresh()->load('items');
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
}
