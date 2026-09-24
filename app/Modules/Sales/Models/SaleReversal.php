<?php

namespace App\Modules\Sales\Models;

use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $sale_id
 * @property int $actor_user_id
 * @property int|null $cash_session_id
 * @property PaymentMethod|null $payment_method
 * @property int $amount_cents
 * @property int $cash_refund_cents
 * @property string $reason
 * @property Carbon $reversed_at
 * @property Carbon $created_at
 * @property-read Sale $sale
 * @property-read User $actor
 * @property-read CashSession|null $cashSession
 */
final class SaleReversal extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'amount_cents' => 'integer',
            'cash_refund_cents' => 'integer',
            'reversed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Sale reversals are immutable.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Sale reversals cannot be deleted.');
        });
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }
}
