<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Models\MenuGroup;
use App\Modules\Hospitality\Models\MenuGroupProduct;
use InvalidArgumentException;

final class AddProductToMenuGroupAction
{
    public function execute(
        MenuGroup $group,
        Product $product,
        int $priceDeltaCents = 0,
        int $sortOrder = 0,
    ): MenuGroupProduct {
        if ($priceDeltaCents < 0 || $sortOrder < 0) {
            throw new InvalidArgumentException('Menü-Aufpreis und Sortierung dürfen nicht negativ sein.');
        }

        return MenuGroupProduct::query()->create([
            'menu_group_id' => $group->id,
            'product_id' => $product->id,
            'price_delta_cents' => $priceDeltaCents,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
