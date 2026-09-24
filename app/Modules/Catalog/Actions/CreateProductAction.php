<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class CreateProductAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        Category $category,
        string $name,
        string $shortName,
        int $priceCents,
        int $sortOrder = 0,
        ?User $actor = null,
    ): Product {
        $name = trim($name);
        $shortName = trim($shortName);

        if ($name === '' || $shortName === '') {
            throw new InvalidArgumentException('Product name and short name are required.');
        }

        if ($priceCents < 0 || $sortOrder < 0) {
            throw new InvalidArgumentException('Price and sort order must not be negative.');
        }

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'short_name' => $shortName,
            'price_cents' => $priceCents,
            'is_consumable' => true,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'product.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Product::class,
            subjectId: $product->id,
            after: $product->only([
                'category_id',
                'name',
                'short_name',
                'price_cents',
                'is_consumable',
                'active',
                'sort_order',
            ]),
        );

        return $product;
    }
}
