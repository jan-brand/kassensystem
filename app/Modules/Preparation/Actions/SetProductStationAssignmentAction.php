<?php

namespace App\Modules\Preparation\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Services\PreparationDispatchService;

final class SetProductStationAssignmentAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
        private readonly PreparationDispatchService $dispatch,
    ) {}

    public function execute(
        PreparationStation $station,
        Product $product,
        bool $assigned,
        ?User $actor = null,
    ): void {
        $station = PreparationStation::query()->findOrFail($station->id);
        $product = Product::query()->findOrFail($product->id);
        $wasAssigned = $station->products()->whereKey($product->id)->exists();

        if ($assigned === $wasAssigned) {
            return;
        }

        if ($assigned) {
            $station->products()->attach($product->id);
            $this->dispatch->backfill($station, $product);
        } else {
            $station->products()->detach($product->id);
        }

        $this->audit->execute(
            eventKey: $assigned ? 'preparation.station.product_assigned' : 'preparation.station.product_unassigned',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: PreparationStation::class,
            subjectId: $station->id,
            before: ['product_id' => $product->id, 'assigned' => $wasAssigned],
            after: ['product_id' => $product->id, 'assigned' => $assigned],
        );
    }
}
