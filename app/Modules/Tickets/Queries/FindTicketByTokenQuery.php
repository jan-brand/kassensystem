<?php

namespace App\Modules\Tickets\Queries;

use App\Modules\Tickets\Models\Ticket;

final class FindTicketByTokenQuery
{
    public function execute(string $token): ?Ticket
    {
        $token = strtolower(trim($token));

        if (! preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return null;
        }

        return Ticket::query()
            ->with(['entitlements.menu.groups.products.product', 'assignedOrder.table.area', 'sale.payment'])
            ->where('token_hash', hash('sha256', $token))
            ->first();
    }
}
