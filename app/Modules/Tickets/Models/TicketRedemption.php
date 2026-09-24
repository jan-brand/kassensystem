<?php

namespace App\Modules\Tickets\Models;

use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $ticket_entitlement_id
 * @property int $hospitality_order_id
 * @property int $hospitality_order_item_id
 * @property int $redeemed_by_user_id
 * @property int $quantity
 * @property int $covered_value_cents
 * @property Carbon $redeemed_at
 */
final class TicketRedemption extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Ticket redemptions are immutable.');
        });

        self::deleting(static function (): never {
            throw new LogicException('Ticket redemptions cannot be deleted.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'covered_value_cents' => 'integer',
            'redeemed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<TicketEntitlement, $this> */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(TicketEntitlement::class, 'ticket_entitlement_id');
    }

    /** @return BelongsTo<HospitalityOrder, $this> */
    public function hospitalityOrder(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrder::class);
    }

    /** @return BelongsTo<HospitalityOrderItem, $this> */
    public function hospitalityOrderItem(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrderItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }
}
