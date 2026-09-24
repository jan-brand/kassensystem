<?php

namespace App\Modules\Tickets\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Models\User;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AssignTicketToHospitalityOrderAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Ticket $ticket, HospitalityOrder $order, User $actor): Ticket
    {
        return DB::transaction(function () use ($ticket, $order, $actor): Ticket {
            $lockedTicket = Ticket::query()->lockForUpdate()->findOrFail($ticket->id);
            $lockedOrder = HospitalityOrder::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedTicket->valid_on->toDateString() !== today()->toDateString()) {
                throw new LogicException('Das Ticket ist heute nicht gültig.');
            }

            if ($lockedTicket->status === TicketStatus::Redeemed) {
                throw new LogicException('Das Ticket ist bereits vollständig eingelöst.');
            }

            if ($lockedOrder->status !== HospitalityOrderStatus::Open) {
                throw new LogicException('Tickets können nur offenen Vorgängen zugeordnet werden.');
            }

            if ($lockedTicket->assigned_order_id !== null) {
                if ($lockedTicket->assigned_order_id !== $lockedOrder->id) {
                    throw new LogicException('Das Ticket ist bereits einem anderen Tisch zugeordnet.');
                }

                return $lockedTicket->load(['entitlements.menu', 'assignedOrder.table.area']);
            }

            $lockedTicket->update([
                'status' => TicketStatus::Assigned,
                'assigned_order_id' => $lockedOrder->id,
                'assigned_table_id' => $lockedOrder->table_id,
                'assigned_at' => now(),
            ]);

            $this->audit->execute(
                eventKey: 'ticket.assigned',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: Ticket::class,
                subjectId: $lockedTicket->id,
                after: [
                    'hospitality_order_id' => $lockedOrder->id,
                    'hospitality_order_number' => $lockedOrder->number,
                    'table_id' => $lockedOrder->table_id,
                ],
            );

            return $lockedTicket->refresh()->load(['entitlements.menu', 'assignedOrder.table.area']);
        });
    }
}
