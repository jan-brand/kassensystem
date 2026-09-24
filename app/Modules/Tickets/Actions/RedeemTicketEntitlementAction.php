<?php

namespace App\Modules\Tickets\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Actions\AddMenuToHospitalityOrderAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Identity\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketEntitlement;
use App\Modules\Tickets\Models\TicketRedemption;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class RedeemTicketEntitlementAction
{
    public function __construct(
        private readonly AddMenuToHospitalityOrderAction $addMenu,
        private readonly WriteAuditEventAction $audit,
    ) {}

    /** @param array<int, list<int>> $selectedProductIdsByGroup */
    public function execute(
        Ticket $ticket,
        TicketEntitlement $entitlement,
        HospitalityOrder $order,
        User $actor,
        array $selectedProductIdsByGroup,
        int $quantity = 1,
        ?string $note = null,
    ): TicketRedemption {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Die Einlösemenge muss mindestens 1 sein.');
        }

        return DB::transaction(function () use (
            $ticket,
            $entitlement,
            $order,
            $actor,
            $selectedProductIdsByGroup,
            $quantity,
            $note,
        ): TicketRedemption {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $lockedEntitlement = TicketEntitlement::query()->lockForUpdate()->findOrFail($entitlement->id);
            $lockedOrder = HospitalityOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedEntitlement->ticket_id !== $lockedTicket->id) {
                throw new LogicException('Die Menüberechtigung gehört nicht zu diesem Ticket.');
            }

            if ($lockedTicket->valid_on->toDateString() !== today()->toDateString()) {
                throw new LogicException('Das Ticket ist heute nicht gültig.');
            }

            if ($lockedTicket->status === TicketStatus::Redeemed) {
                throw new LogicException('Das Ticket ist bereits vollständig eingelöst.');
            }

            if (
                $lockedTicket->assigned_order_id !== $lockedOrder->id
                || $lockedTicket->assigned_table_id !== $lockedOrder->table_id
            ) {
                throw new LogicException('Das Ticket ist diesem Tisch nicht zugeordnet.');
            }

            if ($lockedOrder->status !== HospitalityOrderStatus::Open) {
                throw new LogicException('Nach Tischschluss kann das Ticket nicht weiter eingelöst werden.');
            }

            if ($lockedEntitlement->remainingQuantity() < $quantity) {
                throw new LogicException('Die Menüberechtigung reicht für diese Menge nicht aus.');
            }

            $lastItemId = (int) ($lockedOrder->items()->max('id') ?? 0);

            $lockedOrder = $this->addMenu->execute(
                order: $lockedOrder,
                menu: $lockedEntitlement->menu()->firstOrFail(),
                selectedProductIdsByGroup: $selectedProductIdsByGroup,
                quantity: $quantity,
                note: $note,
            );

            $item = HospitalityOrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->where('id', '>', $lastItemId)
                ->where('menu_id', $lockedEntitlement->menu_id)
                ->orderByDesc('id')
                ->firstOrFail();

            $coveredValueCents = $item->total_cents;

            $item->update([
                'unit_price_cents' => 0,
                'total_cents' => 0,
            ]);

            $lockedOrder->update([
                'total_cents' => (int) $lockedOrder->items()->sum('total_cents'),
            ]);

            $lockedEntitlement->update([
                'quantity_redeemed' => $lockedEntitlement->quantity_redeemed + $quantity,
            ]);

            $redemption = TicketRedemption::query()->create([
                'ticket_id' => $lockedTicket->id,
                'ticket_entitlement_id' => $lockedEntitlement->id,
                'hospitality_order_id' => $lockedOrder->id,
                'hospitality_order_item_id' => $item->id,
                'redeemed_by_user_id' => $actor->id,
                'quantity' => $quantity,
                'covered_value_cents' => $coveredValueCents,
                'redeemed_at' => now(),
            ]);

            $hasRemaining = TicketEntitlement::query()
                ->where('ticket_id', $lockedTicket->id)
                ->whereColumn('quantity_redeemed', '<', 'quantity_allowed')
                ->exists();

            if (! $hasRemaining) {
                $lockedTicket->update([
                    'status' => TicketStatus::Redeemed,
                    'redeemed_at' => now(),
                ]);
            }

            $this->audit->execute(
                eventKey: 'ticket.redeemed',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: Ticket::class,
                subjectId: $lockedTicket->id,
                after: [
                    'ticket_entitlement_id' => $lockedEntitlement->id,
                    'menu_id' => $lockedEntitlement->menu_id,
                    'hospitality_order_id' => $lockedOrder->id,
                    'hospitality_order_item_id' => $item->id,
                    'quantity' => $quantity,
                    'covered_value_cents' => $coveredValueCents,
                ],
            );

            return $redemption->load(['ticket', 'entitlement.menu', 'hospitalityOrderItem']);
        });
    }
}
