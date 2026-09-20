<?php

namespace App\Modules\Sales\Models;

use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

final class Sale extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => SaleStatus::class,
            'subtotal_cents' => 'integer',
            'total_cents' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (Sale $sale): void {
            if ($sale->getRawOriginal('status') === SaleStatus::Completed->value) {
                throw new LogicException('Completed sales are immutable.');
            }
        });

        static::deleting(static function (Sale $sale): void {
            if ($sale->status === SaleStatus::Completed) {
                throw new LogicException('Completed sales cannot be deleted.');
            }
        });
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
