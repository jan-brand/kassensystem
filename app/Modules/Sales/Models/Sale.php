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

/**
 * @property int $id
 * @property string|null $number
 * @property int $register_id
 * @property int $cash_session_id
 * @property int $cashier_id
 * @property SaleStatus $status
 * @property int $subtotal_cents
 * @property int $total_cents
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property-read Register $register
 * @property-read CashSession $cashSession
 * @property-read User $cashier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SaleItem> $items
 * @property-read Payment|null $payment
 */
final class Sale extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
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

    /** @return BelongsTo<Register, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
