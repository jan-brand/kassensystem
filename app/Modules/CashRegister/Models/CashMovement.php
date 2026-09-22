<?php

namespace App\Modules\CashRegister\Models;

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $cash_session_id
 * @property int $user_id
 * @property CashMovementType $type
 * @property int $amount_cents
 * @property string $reason
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read CashSession $cashSession
 * @property-read User $user
 */
final class CashMovement extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CashMovementType::class,
            'amount_cents' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Cash movements are immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Cash movements cannot be deleted.');
        });
    }

    /** @return BelongsTo<CashSession, $this> */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
