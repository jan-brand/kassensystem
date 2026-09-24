<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Models\OptionGroup;

final class AssignOptionGroupToProductAction
{
    public function execute(OptionGroup $group, Product $product): void
    {
        $group->products()->syncWithoutDetaching([$product->id]);
    }
}
