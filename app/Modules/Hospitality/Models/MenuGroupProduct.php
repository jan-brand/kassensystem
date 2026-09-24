<?php

namespace App\Modules\Hospitality\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $menu_group_id
 * @property int $product_id
 * @property int $price_delta_cents
 * @property bool $active
 * @property int $sort_order
 * @property-read MenuGroup $group
 * @property-read Product $product
 */
final class MenuGroupProduct extends Model
{
    protected $table = 'hospitality_menu_group_products';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_delta_cents' => 'integer',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<MenuGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(MenuGroup::class, 'menu_group_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
