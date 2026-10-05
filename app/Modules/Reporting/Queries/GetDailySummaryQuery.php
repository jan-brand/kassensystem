<?php

namespace App\Modules\Reporting\Queries;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Preparation\Models\PreparationTask;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Models\SaleReversal;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketRedemption;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * @phpstan-type ProductRow array{
 *     product_id: int,
 *     product_name: string,
 *     category_name: string,
 *     quantity: int,
 *     gross_cents: int,
 *     discount_cents: int,
 *     revenue_cents: int
 * }
 * @phpstan-type CategoryRow array{
 *     category_name: string,
 *     quantity: int,
 *     gross_cents: int,
 *     discount_cents: int,
 *     revenue_cents: int
 * }
 * @phpstan-type WaiterRow array{user_id: int, name: string, opened_orders: int}
 * @phpstan-type AreaRow array{area_name: string, opened_orders: int}
 * @phpstan-type TableRow array{area_name: string, table_name: string, opened_orders: int}
 * @phpstan-type StationRow array{
 *     station_id: int,
 *     station_name: string,
 *     started_count: int,
 *     ready_count: int,
 *     average_seconds: int|null
 * }
 * @phpstan-type AuditRow array{event_key: string, count: int}
 * @phpstan-type DailySummary array{
 *     date: string,
 *     sales_count: int,
 *     revenue_cents: int,
 *     gross_sales_cents: int,
 *     reversals_count: int,
 *     reversal_cents: int,
 *     net_revenue_cents: int,
 *     free_sales_count: int,
 *     cash_cents: int,
 *     paypal_cents: int,
 *     cash_refund_cents: int,
 *     discount_cents: int,
 *     offer_discount_cents: int,
 *     manual_discount_cents: int,
 *     products: list<ProductRow>,
 *     categories: list<CategoryRow>,
 *     hospitality: array{
 *         opened_orders: int,
 *         closed_orders: int,
 *         waiters: list<WaiterRow>,
 *         areas: list<AreaRow>,
 *         tables: list<TableRow>
 *     },
 *     tickets: array{
 *         issued: int,
 *         paid: int,
 *         free: int,
 *         redemptions: int,
 *         redeemed_quantity: int,
 *         covered_value_cents: int
 *     },
 *     preparation: list<StationRow>,
 *     audit: list<AuditRow>
 * }
 */
final class GetDailySummaryQuery
{
    /** @return DailySummary */
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
        $grossSalesCents = (int) (clone $sales)->sum('total_cents');
        $freeSalesCount = (clone $sales)->where('total_cents', 0)->count();

        $reversals = SaleReversal::query()
            ->whereBetween('reversed_at', [$start, $end]);

        $reversalsCount = (clone $reversals)->count();
        $reversalCents = (int) (clone $reversals)->sum('amount_cents');
        $cashRefundCents = (int) (clone $reversals)->sum('cash_refund_cents');

        $cashCents = (int) Payment::query()
            ->where('method', PaymentMethod::Cash->value)
            ->whereHas('sale', static fn ($query) => $query
                ->where('status', SaleStatus::Completed->value)
                ->whereBetween('completed_at', [$start, $end]))
            ->sum('amount_cents');

        $paypalCents = (int) Payment::query()
            ->where('method', PaymentMethod::Paypal->value)
            ->whereHas('sale', static fn ($query) => $query
                ->where('status', SaleStatus::Completed->value)
                ->whereBetween('completed_at', [$start, $end]))
            ->sum('amount_cents');

        $discountBase = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.completed_at', [$start, $end]);

        $discountExpression = '(COALESCE(sale_items.original_unit_price_cents, sale_items.unit_price_cents) - sale_items.unit_price_cents) * sale_items.quantity';

        $discountCents = (int) (clone $discountBase)->selectRaw("COALESCE(SUM({$discountExpression}), 0) AS total")
            ->value('total');
        $offerDiscountCents = (int) (clone $discountBase)
            ->where('sale_items.discount_source', DiscountSource::Offer->value)
            ->selectRaw("COALESCE(SUM({$discountExpression}), 0) AS total")
            ->value('total');
        $manualDiscountCents = (int) (clone $discountBase)
            ->where('sale_items.discount_source', DiscountSource::Manual->value)
            ->selectRaw("COALESCE(SUM({$discountExpression}), 0) AS total")
            ->value('total');

        $products = SaleItem::query()
            ->select([
                'sale_items.product_id',
                'sale_items.product_name',
                'sale_items.category_name',
            ])
            ->selectRaw('SUM(sale_items.quantity) AS quantity')
            ->selectRaw('SUM(COALESCE(sale_items.original_unit_price_cents, sale_items.unit_price_cents) * sale_items.quantity) AS gross_cents')
            ->selectRaw("SUM({$discountExpression}) AS discount_cents")
            ->selectRaw('SUM(sale_items.total_cents) AS revenue_cents')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.completed_at', [$start, $end])
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.category_name')
            ->orderByDesc('quantity')
            ->orderBy('sale_items.product_name')
            ->get()
            ->map(static fn (SaleItem $item): array => [
                'product_id' => (int) $item->product_id,
                'product_name' => (string) $item->product_name,
                'category_name' => (string) ($item->category_name ?: 'Unbekannt'),
                'quantity' => (int) $item->getAttribute('quantity'),
                'gross_cents' => (int) $item->getAttribute('gross_cents'),
                'discount_cents' => (int) $item->getAttribute('discount_cents'),
                'revenue_cents' => (int) $item->getAttribute('revenue_cents'),
            ])
            ->values()
            ->all();

        $categories = SaleItem::query()
            ->select(['sale_items.category_name'])
            ->selectRaw('SUM(sale_items.quantity) AS quantity')
            ->selectRaw('SUM(COALESCE(sale_items.original_unit_price_cents, sale_items.unit_price_cents) * sale_items.quantity) AS gross_cents')
            ->selectRaw("SUM({$discountExpression}) AS discount_cents")
            ->selectRaw('SUM(sale_items.total_cents) AS revenue_cents')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', SaleStatus::Completed->value)
            ->whereBetween('sales.completed_at', [$start, $end])
            ->groupBy('sale_items.category_name')
            ->orderByDesc('revenue_cents')
            ->orderBy('sale_items.category_name')
            ->get()
            ->map(static fn (SaleItem $item): array => [
                'category_name' => (string) ($item->category_name ?: 'Unbekannt'),
                'quantity' => (int) $item->getAttribute('quantity'),
                'gross_cents' => (int) $item->getAttribute('gross_cents'),
                'discount_cents' => (int) $item->getAttribute('discount_cents'),
                'revenue_cents' => (int) $item->getAttribute('revenue_cents'),
            ])
            ->values()
            ->all();

        $openedOrders = HospitalityOrder::query()
            ->with('openedBy')
            ->whereBetween('opened_at', [$start, $end])
            ->get();

        /** @var array<int, WaiterRow> $waiterMap */
        $waiterMap = [];
        /** @var array<string, AreaRow> $areaMap */
        $areaMap = [];
        /** @var array<string, TableRow> $tableMap */
        $tableMap = [];

        foreach ($openedOrders as $order) {
            $waiterId = $order->opened_by_user_id;
            $waiterMap[$waiterId] ??= [
                'user_id' => $waiterId,
                'name' => $order->openedBy->auditDisplayName(),
                'opened_orders' => 0,
            ];
            $waiterMap[$waiterId]['opened_orders']++;

            $areaName = $order->area_name_snapshot ?: 'Unbekannt';
            $areaMap[$areaName] ??= ['area_name' => $areaName, 'opened_orders' => 0];
            $areaMap[$areaName]['opened_orders']++;

            $tableName = $order->table_name_snapshot ?: 'Unbekannt';
            $tableKey = $areaName."\0".$tableName;
            $tableMap[$tableKey] ??= [
                'area_name' => $areaName,
                'table_name' => $tableName,
                'opened_orders' => 0,
            ];
            $tableMap[$tableKey]['opened_orders']++;
        }

        $waiters = array_values($waiterMap);
        usort($waiters, static fn (array $left, array $right): int => $right['opened_orders'] <=> $left['opened_orders']);

        $areas = array_values($areaMap);
        usort($areas, static fn (array $left, array $right): int => $right['opened_orders'] <=> $left['opened_orders']);

        $tables = array_values($tableMap);
        usort($tables, static fn (array $left, array $right): int => $right['opened_orders'] <=> $left['opened_orders']);

        $tickets = Ticket::query()
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $redemptions = TicketRedemption::query()
            ->whereBetween('redeemed_at', [$start, $end])
            ->get();

        $preparationTasks = PreparationTask::query()
            ->with('station')
            ->where(function ($query) use ($start, $end): void {
                $query
                    ->whereBetween('started_at', [$start, $end])
                    ->orWhereBetween('ready_at', [$start, $end]);
            })
            ->get();

        /**
         * @var array<int, array{
         *     station_id: int,
         *     station_name: string,
         *     started_count: int,
         *     ready_count: int,
         *     duration_total_seconds: int,
         *     duration_count: int
         * }> $stationMap
         */
        $stationMap = [];

        foreach ($preparationTasks as $task) {
            $stationId = $task->station_id;
            $stationMap[$stationId] ??= [
                'station_id' => $stationId,
                'station_name' => $task->station->name,
                'started_count' => 0,
                'ready_count' => 0,
                'duration_total_seconds' => 0,
                'duration_count' => 0,
            ];

            if ($task->started_at !== null && $task->started_at->betweenIncluded($start, $end)) {
                $stationMap[$stationId]['started_count']++;
            }

            if ($task->ready_at !== null && $task->ready_at->betweenIncluded($start, $end)) {
                $stationMap[$stationId]['ready_count']++;

                if ($task->started_at !== null) {
                    $stationMap[$stationId]['duration_total_seconds'] += (int) $task->started_at->diffInSeconds($task->ready_at);
                    $stationMap[$stationId]['duration_count']++;
                }
            }
        }

        $preparation = array_map(
            static fn (array $station): array => [
                'station_id' => $station['station_id'],
                'station_name' => $station['station_name'],
                'started_count' => $station['started_count'],
                'ready_count' => $station['ready_count'],
                'average_seconds' => $station['duration_count'] > 0
                    ? (int) round($station['duration_total_seconds'] / $station['duration_count'])
                    : null,
            ],
            array_values($stationMap),
        );

        usort($preparation, static fn (array $left, array $right): int => strcmp($left['station_name'], $right['station_name']));

        $audit = AuditEvent::query()
            ->select(['event_key'])
            ->selectRaw('COUNT(*) AS event_count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('event_key')
            ->orderByDesc('event_count')
            ->orderBy('event_key')
            ->get()
            ->map(static fn (AuditEvent $event): array => [
                'event_key' => $event->event_key,
                'count' => (int) $event->getAttribute('event_count'),
            ])
            ->values()
            ->all();

        return [
            'date' => $date,
            'sales_count' => $salesCount,
            'revenue_cents' => $grossSalesCents,
            'gross_sales_cents' => $grossSalesCents,
            'reversals_count' => $reversalsCount,
            'reversal_cents' => $reversalCents,
            'net_revenue_cents' => $grossSalesCents - $reversalCents,
            'free_sales_count' => $freeSalesCount,
            'cash_cents' => $cashCents,
            'paypal_cents' => $paypalCents,
            'cash_refund_cents' => $cashRefundCents,
            'discount_cents' => $discountCents,
            'offer_discount_cents' => $offerDiscountCents,
            'manual_discount_cents' => $manualDiscountCents,
            'products' => $products,
            'categories' => $categories,
            'hospitality' => [
                'opened_orders' => $openedOrders->count(),
                'closed_orders' => HospitalityOrder::query()
                    ->whereBetween('closed_at', [$start, $end])
                    ->count(),
                'waiters' => $waiters,
                'areas' => $areas,
                'tables' => $tables,
            ],
            'tickets' => [
                'issued' => $tickets->count(),
                'paid' => $tickets->where('funding_type', TicketFundingType::Paid)->count(),
                'free' => $tickets->where('funding_type', TicketFundingType::Free)->count(),
                'redemptions' => $redemptions->count(),
                'redeemed_quantity' => (int) $redemptions->sum('quantity'),
                'covered_value_cents' => (int) $redemptions->sum('covered_value_cents'),
            ],
            'preparation' => $preparation,
            'audit' => $audit,
        ];
    }
}
