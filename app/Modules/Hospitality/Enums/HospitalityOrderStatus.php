<?php

namespace App\Modules\Hospitality\Enums;

enum HospitalityOrderStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
