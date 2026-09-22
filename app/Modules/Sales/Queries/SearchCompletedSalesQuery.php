<?php

namespace App\Modules\Sales\Queries;

use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class SearchCompletedSalesQuery
{
    /** @return LengthAwarePaginator<int, Sale> */
    public function execute(
        ?string $number = null,
        ?CarbonImmutable $from = null,
        ?CarbonImmutable $to = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        $number = trim((string) $number);

        return Sale::query()
            ->with(['cashier', 'register'])
            ->where('status', SaleStatus::Completed->value)
            ->when($number !== '', fn ($query) => $query->where('number', 'like', '%'.$number.'%'))
            ->when($from, fn ($query, $value) => $query->where('completed_at', '>=', $value))
            ->when($to, fn ($query, $value) => $query->where('completed_at', '<=', $value))
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
