<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public static function parseCents(string $value): int
    {
        $value = trim(str_replace('€', '', $value));
        $value = str_replace(' ', '', $value);

        if ($value === '') {
            throw new InvalidArgumentException('Amount is required.');
        }

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/D', $value, $matches)) {
            throw new InvalidArgumentException('Amount must be a valid euro value with at most two decimal places.');
        }

        $euros = (int) $matches[1];
        $decimal = $matches[2] ?? '';
        $cents = $decimal === '' ? 0 : (int) str_pad($decimal, 2, '0');

        return ($euros * 100) + $cents;
    }

    public static function format(int $cents, string $currency = 'EUR'): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);
        $euros = intdiv($absolute, 100);
        $decimal = $absolute % 100;
        $symbol = $currency === 'EUR' ? '€' : $currency;

        return sprintf(
            '%s%s,%02d %s',
            $negative ? '-' : '',
            number_format($euros, 0, ',', '.'),
            $decimal,
            $symbol,
        );
    }

    public static function decimal(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);

        return sprintf(
            '%s%d,%02d',
            $negative ? '-' : '',
            intdiv($absolute, 100),
            $absolute % 100,
        );
    }
}
