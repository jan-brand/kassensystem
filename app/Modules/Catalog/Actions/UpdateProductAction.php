<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class UpdateProductAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        Product $product,
        Category $category,
        string $name,
        string $shortName,
        int $priceCents,
        int $sortOrder,
        ?User $actor = null,
    ): Product {
        $name = trim($name);
        $shortName = trim($shortName);

        if ($name === '' || $shortName === '' || $priceCents < 0 || $sortOrder < 0) {
            throw new InvalidArgumentException('Invalid product data.');
        }

        $before = $product->only([
            'category_id',
            'name',
            'short_name',
            'price_cents',
            'active',
            'sort_order',
        ]);

        $product->update([
            'category_id' => $category->id,
            'name' => $name,
            'short_name' => $shortName,
            'price_cents' => $priceCents,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'product.updated',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Product::class,
            subjectId: $product->id,
            before: $before,
            after: $product->fresh()->only([
                'category_id',
                'name',
                'short_name',
                'price_cents',
                'active',
                'sort_order',
            ]),
        );

        return $product->refresh();
    }
}
