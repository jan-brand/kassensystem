<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Hospitality\Enums\HospitalityOrderItemKind;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Hospitality\Models\MenuGroupProduct;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class AddMenuToHospitalityOrderAction
{
    /**
     * @param  array<int, list<int>>  $selectedProductIdsByGroup
     */
    public function execute(
        HospitalityOrder $order,
        Menu $menu,
        array $selectedProductIdsByGroup,
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
            $menu,
            $selectedProductIdsByGroup,
            $quantity,
            $note,
        ): HospitalityOrder {
            $locked = HospitalityOrder::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($locked->status !== HospitalityOrderStatus::Open) {
                throw new LogicException('Nur offene Vorgänge können geändert werden.');
            }

            $menu = Menu::query()
                ->with(['groups.products.product.category'])
                ->findOrFail($menu->id);

            if (! $menu->active) {
                throw new LogicException('Menü ist nicht verfügbar.');
            }

            $knownGroupIds = $menu->groups
                ->pluck('id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();

            foreach (array_keys($selectedProductIdsByGroup) as $groupId) {
                if (! in_array((int) $groupId, $knownGroupIds, true)) {
                    throw new LogicException('Eine Menüauswahl gehört nicht zu diesem Menü.');
                }
            }

            $components = [];
            $priceDeltaCents = 0;
            $componentSortOrder = 0;
            $isConsumable = false;

            foreach ($menu->groups as $group) {
                $selectedProductIds = array_values(array_unique(array_map(
                    'intval',
                    $selectedProductIdsByGroup[$group->id] ?? [],
                )));

                if (
                    count($selectedProductIds) < $group->min_choices
                    || count($selectedProductIds) > $group->max_choices
                ) {
                    throw new LogicException("Die Auswahl für {$group->name} ist nicht vollständig oder nicht zulässig.");
                }

                foreach ($selectedProductIds as $productId) {
                    $entry = $group->products->first(
                        static fn (MenuGroupProduct $candidate): bool => (int) $candidate->product_id === $productId,
                    );

                    if (! $entry instanceof MenuGroupProduct || ! $entry->active) {
                        throw new LogicException('Eine Menükomponente ist nicht verfügbar.');
                    }

                    $product = $entry->product;

                    if (! $product->active || ! $product->category->active) {
                        throw new LogicException('Eine Menükomponente ist nicht verfügbar.');
                    }

                    $components[] = [
                        'menu_group_name' => $group->name,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_is_consumable' => $product->is_consumable,
                        'price_delta_cents' => $entry->price_delta_cents,
                        'sort_order' => $componentSortOrder++,
                    ];

                    $priceDeltaCents += $entry->price_delta_cents;
                    $isConsumable = $isConsumable || $product->is_consumable;
                }
            }

            $unitPriceCents = $menu->price_cents + $priceDeltaCents;

            $item = HospitalityOrderItem::query()->create([
                'order_id' => $locked->id,
                'kind' => HospitalityOrderItemKind::Menu,
                'product_id' => null,
                'menu_id' => $menu->id,
                'label' => $menu->name,
                'unit_price_cents' => $unitPriceCents,
                'quantity' => $quantity,
                'total_cents' => $unitPriceCents * $quantity,
                'is_consumable' => $isConsumable,
                'note' => $note === '' ? null : $note,
            ]);

            foreach ($components as $component) {
                HospitalityOrderItemComponent::query()->create([
                    'order_item_id' => $item->id,
                    ...$component,
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
