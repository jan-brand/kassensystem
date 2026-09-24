<?php

namespace App\Modules\Preparation\Services;

use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Hospitality\Models\HospitalityOrderItemOption;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationTask;
use LogicException;

final class PreparationMutationGuard
{
    public function assertItemMutable(HospitalityOrderItem $item): void
    {
        if ($this->hasStartedWork($item->id)) {
            throw new LogicException('Die Position ist bereits in Zubereitung und kann nicht mehr geändert oder entfernt werden.');
        }
    }

    public function assertComponentMutable(HospitalityOrderItemComponent $component): void
    {
        $this->assertItemMutable($component->item()->firstOrFail());
    }

    public function assertOptionMutable(HospitalityOrderItemOption $option): void
    {
        $this->assertItemMutable($option->item()->firstOrFail());
    }

    private function hasStartedWork(int $itemId): bool
    {
        return PreparationTask::query()
            ->where('hospitality_order_item_id', $itemId)
            ->where('status', '!=', PreparationTaskStatus::New->value)
            ->exists();
    }
}
