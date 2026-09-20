<?php

namespace App\Modules\Catalog\Queries;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class GetPosCatalogQuery
{
    /** @return Collection<int, Category> */
    public function execute(): Collection
    {
        return Category::query()
            ->where('active', true)
            ->with([
                'products' => fn ($query) => $query
                    ->where('active', true)
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
