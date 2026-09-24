<?php

namespace App\Modules\Tickets\Enums;

enum TicketFundingType: string
{
    case Free = 'free';
    case Paid = 'paid';
}
