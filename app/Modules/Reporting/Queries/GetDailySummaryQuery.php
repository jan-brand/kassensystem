<?php

namespace App\Modules\Reporting\Queries;

use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class GetDailySummaryQuery
{
    /**
     * @return array{
     *     date: string,
     *     sales_count: int,
     *     revenue_cents: int,
     *     free_sales_count: int,
     *     products: list<array{product_id: int, product_name: string, quantity: int, revenue_cents: int}>
     * }
     */
    public function execute(string $date): array
    {
        $timezone = (string) config('kassensystem.timezone', 'Europe/Berlin');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);

        if ($day === null || $day->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('Date must use the format YYYY-MM-DD.');
        }

        $start = $day->startOfDay();
        $end = $day->endOfDay();

        $sales = Sale::query()
            ->where('status', SaleStatus::Completed->value)
            ->whereBetween('completed_at', [$start, $end]);

        $salesCount = (clone $sales)->count();
        $revenueCents = (int) (clone $sales)->sum('total_cents');
        $freeSalesCount = (clone $sales)->where('total_cents', 0)->count();

        $products = SaleItem::query()
            ->select([
                'sale_items.product_id',
                'sale_items.product_name',
            ])
            ->selectRaw('SUM(sale_items.quantity) AS quantity')
            ->selectRaw('SUM(sale_items.total_cents) AS revenue_cents')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.completed_at', [$start, $end])
            ->groupBy('sale_items.product_id', 'sale_items.product_name')
            ->orderByDesc('quantity')
            ->orderBy('sale_items.product_name')
            ->get()
            ->map(static fn (SaleItem $item): array => [
                'product_id' => (int) $item->product_id,
                'product_name' => (string) $item->product_name,
                'quantity' => (int) $item->getAttribute('quantity'),
                'revenue_cents' => (int) $item->getAttribute('revenue_cents'),
            ])
            ->values()
            ->all();

        return [
            'date' => $date,
            'sales_count' => $salesCount,
            'revenue_cents' => $revenueCents,
            'free_sales_count' => $freeSalesCount,
            'products' => $products,
        ];
    }
}
