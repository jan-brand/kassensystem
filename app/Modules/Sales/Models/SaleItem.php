<?php

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property string $product_name
 * @property int $unit_price_cents
 * @property bool $is_consumable
 * @property int $quantity
 * @property int $total_cents
 * @property-read Sale $sale
 * @property-read Product $product
 */
final class SaleItem extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (SaleItem $item): void {
            if ($item->sale()->where('status', SaleStatus::Completed->value)->exists()) {
                throw new LogicException('Items of completed sales are immutable.');
            }
        });

        self::deleting(static function (SaleItem $item): void {
            if ($item->sale()->where('status', SaleStatus::Completed->value)->exists()) {
                throw new LogicException('Items of completed sales cannot be deleted.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'is_consumable' => 'boolean',
            'quantity' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
