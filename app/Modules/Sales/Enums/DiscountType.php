<?php

namespace App\Modules\Sales\Enums;

enum DiscountType: string
{
    case NewPrice = 'new_price';
    case FixedAmount = 'fixed_amount';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::NewPrice => 'Neuer Endpreis',
            self::FixedAmount => 'Fester Rabattbetrag',
            self::Percentage => 'Prozent',
        };
    }
}
