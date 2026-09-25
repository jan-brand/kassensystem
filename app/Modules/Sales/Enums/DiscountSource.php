<?php

namespace App\Modules\Sales\Enums;

enum DiscountSource: string
{
    case Offer = 'offer';
    case Manual = 'manual';
}
