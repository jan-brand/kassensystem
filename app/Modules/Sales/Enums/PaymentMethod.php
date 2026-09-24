<?php

namespace App\Modules\Sales\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Paypal = 'paypal';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Bar',
            self::Paypal => 'PayPal.me',
        };
    }
}
