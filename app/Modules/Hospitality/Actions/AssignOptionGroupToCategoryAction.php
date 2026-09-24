<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Catalog\Models\Category;
use App\Modules\Hospitality\Models\OptionGroup;

final class AssignOptionGroupToCategoryAction
{
    public function execute(OptionGroup $group, Category $category): void
    {
        $group->categories()->syncWithoutDetaching([$category->id]);
    }
}
