<?php

namespace App\Modules\CashRegister\Enums;

enum CashMovementType: string
{
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
}
