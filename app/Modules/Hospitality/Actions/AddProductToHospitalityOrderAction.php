<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Enums\HospitalityOrderItemKind;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemOption;
use App\Modules\Hospitality\Services\ResolveProductOptionsService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class AddProductToHospitalityOrderAction
{
    public function __construct(private readonly ResolveProductOptionsService $resolveOptions) {}

    /**
     * @param  list<int>  $optionValueIds
     */
    public function execute(
        HospitalityOrder $order,
        Product $product,
        array $optionValueIds = [],
        int $quantity = 1,
        ?string $note = null,
    ): HospitalityOrder {
        $note = $note === null ? null : trim($note);

        if ($quantity < 1) {
            throw new InvalidArgumentException('Die Menge muss mindestens 1 sein.');
        }

        if ($note !== null && mb_strlen($note) > 500) {
            throw new InvalidArgumentException('Die Positionsnotiz darf maximal 500 Zeichen enthalten.');
        }

        return DB::transaction(function () use (
            $order,
            $product,
            $optionValueIds,
            $quantity,
            $note,
        ): HospitalityOrder {
            $locked = HospitalityOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($locked->status !== HospitalityOrderStatus::Open) {
                throw new LogicException('Nur offene Vorgänge können geändert werden.');
            }

            $product = Product::query()
                ->with('category')
                ->findOrFail($product->id);

            if (! $product->active || ! $product->category->active) {
                throw new LogicException('Produkt ist für den Vorgang nicht verfügbar.');
            }

            $selection = $this->resolveOptions->execute($product, $optionValueIds);
            $unitPriceCents = $product->price_cents + $selection['price_delta_cents'];

            $item = HospitalityOrderItem::query()->create([
                'order_id' => $locked->id,
                'kind' => HospitalityOrderItemKind::Product,
                'product_id' => $product->id,
                'menu_id' => null,
                'label' => $product->name,
                'unit_price_cents' => $unitPriceCents,
                'quantity' => $quantity,
                'total_cents' => $unitPriceCents * $quantity,
                'is_consumable' => $product->is_consumable,
                'note' => $note === '' ? null : $note,
            ]);

            foreach ($selection['options'] as $option) {
                HospitalityOrderItemOption::query()->create([
                    'order_item_id' => $item->id,
                    'option_group_name' => $option['group_name'],
                    'option_value_name' => $option['value_name'],
                    'price_delta_cents' => $option['price_delta_cents'],
                    'sort_order' => $option['sort_order'],
                ]);
            }

            $this->recalculate($locked);

            return $locked->refresh()->load(['items.options', 'items.components']);
        });
    }

    private function recalculate(HospitalityOrder $order): void
    {
        $order->update([
            'total_cents' => (int) $order->items()->sum('total_cents'),
        ]);
    }
}
