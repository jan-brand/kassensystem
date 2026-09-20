<?php

namespace App\Modules\CashRegister\Enums;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';
}
