<?php

namespace App\Modules\Preparation\Services;

use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationTask;

final class PreparationSynchronizationService
{
    public function syncItem(HospitalityOrderItem $item): void
    {
        PreparationTask::query()
            ->where('hospitality_order_item_id', $item->id)
            ->where('status', PreparationTaskStatus::New->value)
            ->get()
            ->each(function (PreparationTask $task) use ($item): void {
                $task->update([
                    'label_snapshot' => $item->label,
                    'note_snapshot' => $item->note,
                    'quantity' => $item->quantity,
                ]);
            });
    }

    public function syncComponent(HospitalityOrderItemComponent $component): void
    {
        PreparationTask::query()
            ->where('hospitality_order_item_component_id', $component->id)
            ->where('status', PreparationTaskStatus::New->value)
            ->get()
            ->each(function (PreparationTask $task) use ($component): void {
                $task->update([
                    'component_label_snapshot' => $component->menu_group_name.': '.$component->product_name,
                ]);
            });
    }
}
