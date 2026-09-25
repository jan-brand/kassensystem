<?php

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $product_id
 * @property string $product_name
 * @property int|null $original_unit_price_cents
 * @property int $unit_price_cents
 * @property DiscountType|null $discount_type
 * @property int|null $discount_value
 * @property DiscountSource|null $discount_source
 * @property string|null $discount_label
 * @property int|null $discount_offer_id
 * @property bool $is_consumable
 * @property int $quantity
 * @property int $total_cents
 * @property-read Sale $sale
 * @property-read Product $product
 * @property-read DiscountOffer|null $discountOffer
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
            'original_unit_price_cents' => 'integer',
            'unit_price_cents' => 'integer',
            'discount_type' => DiscountType::class,
            'discount_value' => 'integer',
            'discount_source' => DiscountSource::class,
            'is_consumable' => 'boolean',
            'quantity' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function discountCents(): int
    {
        $original = $this->original_unit_price_cents ?? $this->unit_price_cents;

        return max(0, ($original - $this->unit_price_cents) * $this->quantity);
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

    /** @return BelongsTo<DiscountOffer, $this> */
    public function discountOffer(): BelongsTo
    {
        return $this->belongsTo(DiscountOffer::class);
    }
}
