<?php

namespace App\Modules\Tickets\Models;

use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $token_hash
 * @property TicketStatus $status
 * @property TicketFundingType $funding_type
 * @property Carbon $valid_on
 * @property int|null $sale_id
 * @property int $issued_by_user_id
 * @property int|null $assigned_order_id
 * @property int|null $assigned_table_id
 * @property Carbon|null $assigned_at
 * @property Carbon|null $redeemed_at
 * @property-read Sale|null $sale
 * @property-read User $issuedBy
 * @property-read HospitalityOrder|null $assignedOrder
 * @property-read DiningTable|null $assignedTable
 * @property-read Collection<int, TicketEntitlement> $entitlements
 * @property-read Collection<int, TicketRedemption> $redemptions
 */
final class Ticket extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (Ticket $ticket): void {
            foreach (['token_hash', 'funding_type', 'valid_on', 'sale_id', 'issued_by_user_id'] as $field) {
                if ($ticket->isDirty($field)) {
                    throw new LogicException('Issued ticket data is immutable.');
                }
            }

            if ($ticket->getRawOriginal('assigned_order_id') !== null && (
                $ticket->isDirty('assigned_order_id') || $ticket->isDirty('assigned_table_id')
            )) {
                throw new LogicException('Ticket assignment cannot be changed.');
            }

            $from = TicketStatus::from((string) $ticket->getRawOriginal('status'));
            $to = $ticket->status;

            $validTransition = $from === $to
                || ($from === TicketStatus::Issued && $to === TicketStatus::Assigned)
                || ($from === TicketStatus::Assigned && $to === TicketStatus::Redeemed);

            if (! $validTransition) {
                throw new LogicException('Invalid ticket status transition.');
            }
        });

        self::deleting(static function (): never {
            throw new LogicException('Tickets cannot be deleted.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'funding_type' => TicketFundingType::class,
            'valid_on' => 'date',
            'assigned_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<User, $this> */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    /** @return BelongsTo<HospitalityOrder, $this> */
    public function assignedOrder(): BelongsTo
    {
        return $this->belongsTo(HospitalityOrder::class, 'assigned_order_id');
    }

    /** @return BelongsTo<DiningTable, $this> */
    public function assignedTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'assigned_table_id');
    }

    /** @return HasMany<TicketEntitlement, $this> */
    public function entitlements(): HasMany
    {
        return $this->hasMany(TicketEntitlement::class)->orderBy('id');
    }

    /** @return HasMany<TicketRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(TicketRedemption::class)->orderBy('id');
    }
}
