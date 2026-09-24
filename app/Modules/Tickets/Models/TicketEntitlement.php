<?php

namespace App\Modules\Tickets\Models;

use App\Modules\Hospitality\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $ticket_id
 * @property int $menu_id
 * @property string $menu_name_snapshot
 * @property int $quantity_allowed
 * @property int $quantity_redeemed
 * @property-read Ticket $ticket
 * @property-read Menu $menu
 */
final class TicketEntitlement extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        self::updating(static function (TicketEntitlement $entitlement): void {
            foreach (array_keys($entitlement->getDirty()) as $field) {
                if (! in_array($field, ['quantity_redeemed', 'updated_at'], true)) {
                    throw new LogicException('Ticket entitlement data is immutable.');
                }
            }

            if (
                $entitlement->quantity_redeemed < (int) $entitlement->getRawOriginal('quantity_redeemed')
                || $entitlement->quantity_redeemed > $entitlement->quantity_allowed
            ) {
                throw new LogicException('Invalid ticket entitlement consumption.');
            }
        });

        self::deleting(static function (): never {
            throw new LogicException('Ticket entitlements cannot be deleted.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity_allowed' => 'integer',
            'quantity_redeemed' => 'integer',
        ];
    }

    /** @return BelongsTo<Ticket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** @return BelongsTo<Menu, $this> */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function remainingQuantity(): int
    {
        return $this->quantity_allowed - $this->quantity_redeemed;
    }
}
