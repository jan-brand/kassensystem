<?php

namespace App\Modules\Hospitality\Models;

use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $number
 * @property int $table_id
 * @property int|null $open_table_id
 * @property string|null $area_name_snapshot
 * @property string|null $table_name_snapshot
 * @property int $opened_by_user_id
 * @property HospitalityOrderStatus $status
 * @property string|null $note
 * @property int $total_cents
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property-read DiningTable $table
 * @property-read User $openedBy
 * @property-read Collection<int, HospitalityOrderItem> $items
 */
final class HospitalityOrder extends Model
{
    protected $table = 'hospitality_orders';

    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (HospitalityOrder $order): void {
            if ($order->getRawOriginal('status') === HospitalityOrderStatus::Closed->value) {
                throw new LogicException('Closed hospitality orders are immutable.');
            }
        });

        self::deleting(static function (HospitalityOrder $order): void {
            if ($order->status === HospitalityOrderStatus::Closed) {
                throw new LogicException('Closed hospitality orders cannot be deleted.');
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => HospitalityOrderStatus::class,
            'total_cents' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<DiningTable, $this> */
    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    /** @return BelongsTo<User, $this> */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /** @return HasMany<HospitalityOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(HospitalityOrderItem::class, 'order_id')
            ->orderBy('id');
    }
}
