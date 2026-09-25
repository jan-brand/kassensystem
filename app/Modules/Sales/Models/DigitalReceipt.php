<?php

namespace App\Modules\Sales\Models;

use App\Modules\Sales\Enums\DigitalReceiptKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $sale_id
 * @property int|null $sale_reversal_id
 * @property DigitalReceiptKind $kind
 * @property string $token_hash
 * @property string $token_ciphertext
 * @property string $merchant_name_snapshot
 * @property string $sale_number_snapshot
 * @property string $currency_snapshot
 * @property Carbon $issued_at
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property-read Sale $sale
 * @property-read SaleReversal|null $reversal
 */
final class DigitalReceipt extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kind' => DigitalReceiptKind::class,
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Digital receipts are immutable.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Digital receipts cannot be deleted.');
        });
    }

    public function isExpired(): bool
    {
        return ! $this->expires_at->isFuture();
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<SaleReversal, $this> */
    public function reversal(): BelongsTo
    {
        return $this->belongsTo(SaleReversal::class, 'sale_reversal_id');
    }
}
