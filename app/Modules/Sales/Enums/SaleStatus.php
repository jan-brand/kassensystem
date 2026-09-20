<?php

namespace App\Modules\Sales\Enums;

enum SaleStatus: string
{
    case Open = 'open';
    case Completed = 'completed';
}
