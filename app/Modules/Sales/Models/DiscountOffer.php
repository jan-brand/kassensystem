<?php

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Enums\DiscountType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property DiscountType $type
 * @property int $value
 * @property bool $active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property list<int>|null $weekdays
 * @property string|null $daily_start_time
 * @property string|null $daily_end_time
 * @property-read Product $product
 */
final class DiscountOffer extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'value' => 'integer',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'weekdays' => 'array',
        ];
    }

    public function isEffectiveAt(CarbonInterface $moment): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->starts_at !== null && $moment->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at !== null && $moment->gt($this->ends_at)) {
            return false;
        }

        $weekdays = $this->weekdays ?? [];

        if ($weekdays !== [] && ! in_array((int) $moment->format('N'), $weekdays, true)) {
            return false;
        }

        if ($this->daily_start_time !== null && $this->daily_end_time !== null) {
            $time = $moment->format('H:i');

            if ($time < $this->daily_start_time || $time > $this->daily_end_time) {
                return false;
            }
        }

        return true;
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
