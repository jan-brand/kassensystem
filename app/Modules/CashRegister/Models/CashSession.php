<?php

namespace App\Modules\CashRegister\Models;

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $register_id
 * @property int $opened_by_user_id
 * @property int|null $closed_by_user_id
 * @property CashSessionStatus $status
 * @property int $opening_cash_cents
 * @property int $cash_sales_cents
 * @property int $cash_refunds_cents
 * @property int|null $closing_expected_cash_cents
 * @property int|null $closing_counted_cash_cents
 * @property int|null $closing_difference_cents
 * @property string|null $closing_comment
 * @property Carbon $opened_at
 * @property Carbon|null $closing_started_at
 * @property Carbon|null $closed_at
 * @property-read Register $register
 * @property-read User $openedBy
 * @property-read User|null $closedBy
 * @property-read Collection<int, CashMovement> $movements
 */
final class CashSession extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => CashSessionStatus::class,
            'opening_cash_cents' => 'integer',
            'cash_sales_cents' => 'integer',
            'cash_refunds_cents' => 'integer',
            'closing_expected_cash_cents' => 'integer',
            'closing_counted_cash_cents' => 'integer',
            'closing_difference_cents' => 'integer',
            'opened_at' => 'datetime',
            'closing_started_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (CashSession $session): void {
            if ($session->getRawOriginal('status') === CashSessionStatus::Closed->value) {
                throw new LogicException('Closed cash sessions are immutable.');
            }
        });

        self::deleting(static function (CashSession $session): void {
            if ($session->status === CashSessionStatus::Closed) {
                throw new LogicException('Closed cash sessions cannot be deleted.');
            }
        });
    }

    /** @return BelongsTo<Register, $this> */
    public function register(): BelongsTo
    {
        return $this->belongsTo(Register::class);
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function expectedCashCents(): int
    {
        $deposits = (int) $this->movements()
            ->where('type', CashMovementType::Deposit->value)
            ->sum('amount_cents');

        $withdrawals = (int) $this->movements()
            ->where('type', CashMovementType::Withdrawal->value)
            ->sum('amount_cents');

        return $this->opening_cash_cents
            + $this->cash_sales_cents
            - $this->cash_refunds_cents
            + $deposits
            - $withdrawals;
    }
}
