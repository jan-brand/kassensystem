<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\SaleNumberSequence;

final class SaleNumberGenerator
{
    public function next(): string
    {
        $year = now(config('kassensystem.timezone', 'Europe/Berlin'))->year;

        $sequence = SaleNumberSequence::query()
            ->lockForUpdate()
            ->firstOrCreate(
                ['year' => $year],
                ['last_number' => 0],
            );

        $sequence->increment('last_number');
        $sequence->refresh();

        return sprintf('%d-%06d', $year, $sequence->last_number);
    }
}
