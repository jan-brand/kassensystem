<?php

namespace App\Modules\Sales\Models;

use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $sale_id
 * @property PaymentMethod $method
 * @property int $amount_cents
 * @property int $received_cents
 * @property int $change_cents
 * @property int|null $confirmed_by_user_id
 * @property string|null $provider_reference
 * @property Carbon $completed_at
 * @property Carbon $created_at
 * @property-read Sale $sale
 * @property-read User|null $confirmedBy
 */
final class Payment extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'amount_cents' => 'integer',
            'received_cents' => 'integer',
            'change_cents' => 'integer',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Payments are immutable.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Payments cannot be deleted.');
        });
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }
}
