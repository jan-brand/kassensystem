<?php

namespace App\Modules\Hospitality\Models;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Enums\HospitalityOrderItemKind;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * @property int $id
 * @property int $order_id
 * @property HospitalityOrderItemKind $kind
 * @property int|null $product_id
 * @property int|null $menu_id
 * @property string $label
 * @property int $unit_price_cents
 * @property int $quantity
 * @property int $total_cents
 * @property bool $is_consumable
 * @property string|null $note
 * @property-read HospitalityOrder $order
 * @property-read Collection<int, HospitalityOrderItemOption> $options
 * @property-read Collection<int, HospitalityOrderItemComponent> $components
 */
final class HospitalityOrderItem extends Model
{
    protected $table = 'hospitality_order_items';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (HospitalityOrderItem $item): void {
            if ($item->order()->where('status', HospitalityOrderStatus::Closed->value)->exists()) {
                throw new LogicException('Items of closed hospitality orders are immutable.');
            }
        });

        self::deleting(static function (HospitalityOrderItem $item): void {
            if ($item->order()->where('status', HospitalityOrderStatus::Closed->value)->exists()) {
                throw new LogicException('Items of closed hospitality orders cannot be deleted.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => HospitalityOrderItemKind::class,
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'total_cents' => 'integer',
            'is_consumable' => 'boolean',
        ];
    }

    /** @return BelongsTo<HospitalityOrder, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrder::class, 'order_id');
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /** @return HasMany<HospitalityOrderItemOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(HospitalityOrderItemOption::class, 'order_item_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    /** @return HasMany<HospitalityOrderItemComponent, $this> */
    public function components(): HasMany
    {
        return $this->hasMany(HospitalityOrderItemComponent::class, 'order_item_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
