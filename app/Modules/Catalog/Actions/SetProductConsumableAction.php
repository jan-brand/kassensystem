<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;

final class SetProductConsumableAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Product $product, bool $isConsumable, ?User $actor = null): Product
    {
        if ($product->is_consumable === $isConsumable) {
            return $product;
        }

        $before = ['is_consumable' => $product->is_consumable];
        $product->update(['is_consumable' => $isConsumable]);

        $this->audit->execute(
            eventKey: 'product.consumable_classification_changed',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Product::class,
            subjectId: $product->id,
            before: $before,
            after: ['is_consumable' => $product->is_consumable],
        );

        return $product->refresh();
    }
}
