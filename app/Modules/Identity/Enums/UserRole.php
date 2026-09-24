<?php

namespace App\Modules\Identity\Enums;

enum UserRole: string
{
    case Cashier = 'cashier';
    case Waiter = 'waiter';
    case Manager = 'manager';
    case Administrator = 'administrator';
}
