<?php

namespace App\Modules\Sales\Enums;

enum DigitalReceiptKind: string
{
    case Sale = 'sale';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Beleg',
            self::Reversal => 'Stornobeleg',
        };
    }
}
