<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;

final class SetProductActiveAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Product $product, bool $active, ?User $actor = null): Product
    {
        $before = ['active' => $product->active];
        $product->update(['active' => $active]);

        $this->audit->execute(
            eventKey: $active ? 'product.activated' : 'product.deactivated',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Product::class,
            subjectId: $product->id,
            before: $before,
            after: ['active' => $product->active],
        );

        return $product->refresh();
    }
}
