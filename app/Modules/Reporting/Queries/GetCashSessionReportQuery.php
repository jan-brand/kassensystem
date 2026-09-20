<?php

namespace App\Modules\Reporting\Queries;

use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

final class GetCashSessionReportQuery
{
    /** @return Collection<int, CashSession> */
    public function execute(string $date): Collection
    {
        $timezone = (string) config('kassensystem.timezone', 'Europe/Berlin');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($day === false || $day->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date must use the format YYYY-MM-DD.');
        }

        return CashSession::query()
            ->with(['register', 'openedBy', 'closedBy'])
            ->where('status', CashSessionStatus::Closed->value)
            ->whereBetween('closed_at', [$day->startOfDay(), $day->endOfDay()])
            ->orderBy('closed_at')
            ->get();
    }
}
