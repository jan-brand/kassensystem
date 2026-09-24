<?php

namespace App\Modules\Hospitality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_item_id
 * @property string $option_group_name
 * @property string $option_value_name
 * @property int $price_delta_cents
 * @property int $sort_order
 */
final class HospitalityOrderItemOption extends Model
{
    protected $table = 'hospitality_order_item_options';

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_delta_cents' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<HospitalityOrderItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrderItem::class, 'order_item_id');
    }
}
