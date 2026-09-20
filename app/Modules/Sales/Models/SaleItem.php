<?php

namespace App\Modules\Sales\Models;

use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class SaleItem extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(static function (SaleItem $item): void {
            if ($item->sale()->where('status', \App\Modules\Sales\Enums\SaleStatus::Completed->value)->exists()) {
                throw new LogicException('Items of completed sales are immutable.');
            }
        });

        static::deleting(static function (SaleItem $item): void {
            if ($item->sale()->where('status', \App\Modules\Sales\Enums\SaleStatus::Completed->value)->exists()) {
                throw new LogicException('Items of completed sales cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'unit_price_cents' => 'integer',
            'quantity' => 'integer',
            'total_cents' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
