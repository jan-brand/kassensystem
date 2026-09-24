<?php

namespace App\Modules\Hospitality\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_item_id
 * @property string $menu_group_name
 * @property int|null $product_id
 * @property string $product_name
 * @property bool $product_is_consumable
 * @property int $price_delta_cents
 * @property int $sort_order
 */
final class HospitalityOrderItemComponent extends Model
{
    protected $table = 'hospitality_order_item_components';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'product_is_consumable' => 'boolean',
            'price_delta_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<HospitalityOrderItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrderItem::class, 'order_item_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
