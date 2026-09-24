<?php

namespace App\Modules\Hospitality\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Models\OptionGroup;
use App\Modules\Hospitality\Models\OptionValue;
use LogicException;

final class ResolveProductOptionsService
{
    /**
     * @param  list<int>  $selectedValueIds
     * @return array{price_delta_cents:int,options:list<array{group_name:string,value_name:string,price_delta_cents:int,sort_order:int}>}
     */
    public function execute(Product $product, array $selectedValueIds): array
    {
        $selectedValueIds = array_values(array_unique(array_map('intval', $selectedValueIds)));

        $groups = OptionGroup::query()
            ->where('active', true)
            ->where(function ($query) use ($product): void {
                $query
                    ->whereHas('products', fn ($q) => $q->whereKey($product->id))
                    ->orWhereHas('categories', fn ($q) => $q->whereKey($product->category_id));
            })
            ->with([
                'values' => fn ($query) => $query->where('active', true),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $allowedValueIds = [];

        foreach ($groups as $group) {
            foreach ($group->values as $value) {
                $allowedValueIds[] = (int) $value->id;
            }
        }

        foreach ($selectedValueIds as $valueId) {
            if (! in_array($valueId, $allowedValueIds, true)) {
                throw new LogicException('Eine ausgewählte Option ist für dieses Produkt nicht verfügbar.');
            }
        }

        $snapshots = [];
        $priceDeltaCents = 0;
        $sortOrder = 0;

        foreach ($groups as $group) {
            $selected = $group->values
                ->filter(
                    static fn (OptionValue $value): bool => in_array((int) $value->id, $selectedValueIds, true),
                )
                ->values();

            if ($selected->count() < $group->min_choices || $selected->count() > $group->max_choices) {
                throw new LogicException("Die Optionen für {$group->name} sind nicht vollständig oder nicht zulässig.");
            }

            foreach ($selected as $value) {
                $snapshots[] = [
                    'group_name' => $group->name,
                    'value_name' => $value->name,
                    'price_delta_cents' => $value->price_delta_cents,
                    'sort_order' => $sortOrder++,
                ];
                $priceDeltaCents += $value->price_delta_cents;
            }
        }

        return [
            'price_delta_cents' => $priceDeltaCents,
            'options' => $snapshots,
        ];
    }
}
