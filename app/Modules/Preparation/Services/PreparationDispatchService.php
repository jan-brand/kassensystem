<?php

namespace App\Modules\Preparation\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Models\PreparationTask;
use Illuminate\Database\Eloquent\Collection;

final class PreparationDispatchService
{
    public function dispatchItem(HospitalityOrderItem $item, ?PreparationStation $onlyStation = null): void
    {
        if ($item->product_id === null) {
            return;
        }

        foreach ($this->stationsForProduct($item->product_id, $onlyStation) as $station) {
            $this->createTask(
                station: $station,
                item: $item,
                component: null,
                productId: $item->product_id,
                sourceKey: 'item:'.$item->id,
                componentLabel: null,
            );
        }
    }

    public function dispatchComponent(
        HospitalityOrderItemComponent $component,
        ?PreparationStation $onlyStation = null,
    ): void {
        if ($component->product_id === null) {
            return;
        }

        $item = $component->item()->firstOrFail();

        foreach ($this->stationsForProduct($component->product_id, $onlyStation) as $station) {
            $this->createTask(
                station: $station,
                item: $item,
                component: $component,
                productId: $component->product_id,
                sourceKey: 'component:'.$component->id,
                componentLabel: $component->menu_group_name.': '.$component->product_name,
            );
        }
    }

    public function backfill(PreparationStation $station, Product $product): void
    {
        if (! $station->active) {
            return;
        }

        HospitalityOrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->orderBy('id')
            ->each(fn (HospitalityOrderItem $item) => $this->dispatchItem($item, $station));

        HospitalityOrderItemComponent::query()
            ->where('product_id', $product->id)
            ->whereHas('item.order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->orderBy('id')
            ->each(fn (HospitalityOrderItemComponent $component) => $this->dispatchComponent($component, $station));
    }

    public function backfillStation(PreparationStation $station): void
    {
        $station->products()->get()->each(
            fn (Product $product) => $this->backfill($station, $product),
        );
    }

    /** @return Collection<int, PreparationStation> */
    private function stationsForProduct(int $productId, ?PreparationStation $onlyStation): Collection
    {
        return PreparationStation::query()
            ->where('active', true)
            ->when($onlyStation instanceof PreparationStation, fn ($query) => $query->whereKey($onlyStation->id))
            ->whereHas('products', fn ($query) => $query->whereKey($productId))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function createTask(
        PreparationStation $station,
        HospitalityOrderItem $item,
        ?HospitalityOrderItemComponent $component,
        int $productId,
        string $sourceKey,
        ?string $componentLabel,
    ): void {
        PreparationTask::query()->firstOrCreate(
            [
                'station_id' => $station->id,
                'source_key' => $sourceKey,
            ],
            [
                'hospitality_order_id' => $item->order_id,
                'hospitality_order_item_id' => $item->id,
                'hospitality_order_item_component_id' => $component?->id,
                'product_id' => $productId,
                'label_snapshot' => $item->label,
                'component_label_snapshot' => $componentLabel,
                'note_snapshot' => $item->note,
                'quantity' => $item->quantity,
                'status' => PreparationTaskStatus::New,
            ],
        );
    }
}
