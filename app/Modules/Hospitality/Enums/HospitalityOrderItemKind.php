<?php

namespace App\Modules\Hospitality\Enums;

enum HospitalityOrderItemKind: string
{
    case Product = 'product';
    case Menu = 'menu';
}
