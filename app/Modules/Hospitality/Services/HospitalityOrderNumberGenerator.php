<?php

namespace App\Modules\Hospitality\Services;

use App\Modules\Hospitality\Models\HospitalityOrderNumberSequence;

final class HospitalityOrderNumberGenerator
{
    public function next(): string
    {
        $year = now(config('kassensystem.timezone', 'Europe/Berlin'))->year;

        $sequence = HospitalityOrderNumberSequence::query()
            ->lockForUpdate()
            ->firstOrCreate(
                ['year' => $year],
                ['last_number' => 0],
            );

        $sequence->increment('last_number');
        $sequence->refresh();

        return sprintf('V-%d-%06d', $year, $sequence->last_number);
    }
}
