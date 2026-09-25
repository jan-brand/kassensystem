<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Enums\DiscountType;
use InvalidArgumentException;

final class DiscountPriceCalculator
{
    public function finalUnitPrice(int $originalPriceCents, DiscountType $type, int $value): int
    {
        if ($originalPriceCents < 0 || $value < 0) {
            throw new InvalidArgumentException('Rabattwerte dürfen nicht negativ sein.');
        }

        return match ($type) {
            DiscountType::NewPrice => $this->newPrice($originalPriceCents, $value),
            DiscountType::FixedAmount => max(0, $originalPriceCents - $value),
            DiscountType::Percentage => $this->percentagePrice($originalPriceCents, $value),
        };
    }

    public function parsePercentageBasisPoints(string $value): int
    {
        $normalized = str_replace(',', '.', trim($value));

        if (! preg_match('/^(?:\d{1,2}|100)(?:\.\d{1,2})?$/D', $normalized)) {
            throw new InvalidArgumentException('Prozentwert muss zwischen 0 und 100 liegen.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '');
        $basisPoints = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        if ($basisPoints > 10000) {
            throw new InvalidArgumentException('Prozentwert darf 100 % nicht überschreiten.');
        }

        return $basisPoints;
    }

    public function formatPercentageBasisPoints(int $basisPoints): string
    {
        if ($basisPoints < 0 || $basisPoints > 10000) {
            throw new InvalidArgumentException('Ungültiger Prozentwert.');
        }

        return intdiv($basisPoints, 100).','.str_pad((string) ($basisPoints % 100), 2, '0', STR_PAD_LEFT);
    }

    private function newPrice(int $originalPriceCents, int $newPriceCents): int
    {
        if ($newPriceCents > $originalPriceCents) {
            throw new InvalidArgumentException('Der neue Endpreis darf den Ausgangspreis nicht überschreiten.');
        }

        return $newPriceCents;
    }

    private function percentagePrice(int $originalPriceCents, int $basisPoints): int
    {
        if ($basisPoints > 10000) {
            throw new InvalidArgumentException('Prozentwert darf 100 % nicht überschreiten.');
        }

        $discountCents = intdiv(($originalPriceCents * $basisPoints) + 5000, 10000);

        return max(0, $originalPriceCents - $discountCents);
    }
}
