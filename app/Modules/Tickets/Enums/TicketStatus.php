<?php

namespace App\Modules\Tickets\Enums;

enum TicketStatus: string
{
    case Issued = 'issued';
    case Assigned = 'assigned';
    case Redeemed = 'redeemed';
}
