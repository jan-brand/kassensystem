<?php

namespace App\Modules\Reporting\Services;

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

/** @phpstan-import-type DailySummary from GetDailySummaryQuery */
final class DailyReportCsvExporter
{
    public function __construct(private readonly CsvExporter $csv) {}

    /**
     * @param  DailySummary  $summary
     * @param  Collection<int, CashSession>  $sessions
     */
    public function export(array $summary, Collection $sessions): string
    {
        $tail = ['', '', '', '', '', '', '', '', ''];

        $rows = [[
            'Übersicht',
            $summary['date'],
            '',
            'Tagesumsatz',
            $summary['sales_count'],
            Money::decimal($summary['revenue_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            ...$tail,
        ], [
            'Übersicht',
            $summary['date'],
            '',
            'Kostenlose Verkäufe',
            $summary['free_sales_count'],
            '0,00',
            '', '', '', '', '', '', '', '', '', '', '', '',
            ...$tail,
        ], [
            'Buchhaltung',
            $summary['date'],
            '',
            'Bruttoverkauf',
            $summary['sales_count'],
            Money::decimal($summary['gross_sales_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', Money::decimal($summary['gross_sales_cents']), '', '', '', '', '', '', 'Kein DATEV-/Steuerexport',
        ], [
            'Buchhaltung',
            $summary['date'],
            '',
            'Storno',
            $summary['reversals_count'],
            Money::decimal($summary['reversal_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', Money::decimal($summary['reversal_cents']), '', '', '', Money::decimal($summary['cash_refund_cents']), 'Gegenbuchungen des Berichtstags',
        ], [
            'Buchhaltung',
            $summary['date'],
            '',
            'Netto-Umsatz',
            $summary['sales_count'],
            Money::decimal($summary['net_revenue_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', Money::decimal($summary['net_revenue_cents']), '', '', '', '',
        ], [
            'Zahlung',
            $summary['date'],
            '',
            'Bar',
            '',
            Money::decimal($summary['cash_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', Money::decimal($summary['cash_cents']), '', '', '',
        ], [
            'Zahlung',
            $summary['date'],
            '',
            'PayPal.me',
            '',
            Money::decimal($summary['paypal_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', Money::decimal($summary['paypal_cents']), '', '',
        ], [
            'Rabatt',
            $summary['date'],
            '',
            'Gesamt',
            '',
            Money::decimal($summary['discount_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', Money::decimal($summary['discount_cents']), '', '', '', '', '', '',
        ], [
            'Rabatt',
            $summary['date'],
            '',
            'Tagesangebote',
            '',
            Money::decimal($summary['offer_discount_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', Money::decimal($summary['offer_discount_cents']), '', '', '', '', '', '',
        ], [
            'Rabatt',
            $summary['date'],
            '',
            'Manuell',
            '',
            Money::decimal($summary['manual_discount_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', Money::decimal($summary['manual_discount_cents']), '', '', '', '', '', '',
        ]];

        foreach ($summary['products'] as $product) {
            $rows[] = [
                'Produkt',
                $summary['date'],
                '',
                $product['product_name'],
                $product['quantity'],
                Money::decimal($product['revenue_cents']),
                '', '', '', '', '', '', '', '', '', '', '', '',
                $product['category_name'],
                Money::decimal($product['gross_cents']),
                Money::decimal($product['discount_cents']),
                '',
                Money::decimal($product['revenue_cents']),
                '', '', '', '',
            ];
        }

        foreach ($summary['categories'] as $category) {
            $rows[] = [
                'Kategorie',
                $summary['date'],
                '',
                $category['category_name'],
                $category['quantity'],
                Money::decimal($category['revenue_cents']),
                '', '', '', '', '', '', '', '', '', '', '', '',
                $category['category_name'],
                Money::decimal($category['gross_cents']),
                Money::decimal($category['discount_cents']),
                '',
                Money::decimal($category['revenue_cents']),
                '', '', '', '',
            ];
        }

        foreach ($summary['hospitality']['waiters'] as $waiter) {
            $rows[] = [
                'Kellner',
                $summary['date'],
                '',
                $waiter['name'],
                $waiter['opened_orders'],
                '',
                '', '', '', '', '', '', '', '', '', '', '', '',
                ...$tail,
            ];
        }

        foreach ($summary['hospitality']['areas'] as $area) {
            $rows[] = [
                'Bereich',
                $summary['date'],
                '',
                $area['area_name'],
                $area['opened_orders'],
                '',
                '', '', '', '', '', '', '', '', '', '', '', '',
                ...$tail,
            ];
        }

        foreach ($summary['hospitality']['tables'] as $table) {
            $rows[] = [
                'Tisch',
                $summary['date'],
                $table['area_name'],
                $table['table_name'],
                $table['opened_orders'],
                '',
                '', '', '', '', '', '', '', '', '', '', '', '',
                ...$tail,
            ];
        }

        $rows[] = [
            'Tickets',
            $summary['date'],
            '',
            'Ausgegeben',
            $summary['tickets']['issued'],
            '',
            '', '', '', '', '', '', '', '', '', '', '', '',
            ...$tail,
        ];
        $rows[] = [
            'Tickets',
            $summary['date'],
            '',
            'Einlösungen',
            $summary['tickets']['redeemed_quantity'],
            Money::decimal($summary['tickets']['covered_value_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
            '', '', '', '', '', '', '', '', 'Kein zusätzlicher Umsatz',
        ];

        foreach ($summary['preparation'] as $station) {
            $rows[] = [
                'Zubereitung',
                $summary['date'],
                '',
                $station['station_name'],
                $station['ready_count'],
                '',
                '', '', '', '', '', '', '', '', '', '', '', '',
                '', '', '', '', '', '', '', '',
                $station['average_seconds'] === null
                    ? "Starts {$station['started_count']} · Fertig {$station['ready_count']}"
                    : "Starts {$station['started_count']} · Fertig {$station['ready_count']} · Ø {$station['average_seconds']} s",
            ];
        }

        foreach ($summary['audit'] as $event) {
            $rows[] = [
                'Audit',
                $summary['date'],
                '',
                $event['event_key'],
                $event['count'],
                '',
                '', '', '', '', '', '', '', '', '', '', '', '',
                ...$tail,
            ];
        }

        foreach ($sessions as $session) {
            $deposits = (int) $session->movements
                ->filter(static fn ($movement): bool => $movement->type === CashMovementType::Deposit)
                ->sum('amount_cents');
            $withdrawals = (int) $session->movements
                ->filter(static fn ($movement): bool => $movement->type === CashMovementType::Withdrawal)
                ->sum('amount_cents');

            $rows[] = [
                'Kassenschicht',
                $summary['date'],
                $session->register->name,
                '#'.$session->id,
                '',
                '',
                Money::decimal((int) $session->opening_cash_cents),
                Money::decimal((int) $session->cash_sales_cents),
                Money::decimal($deposits),
                Money::decimal($withdrawals),
                Money::decimal((int) $session->closing_expected_cash_cents),
                Money::decimal((int) $session->closing_counted_cash_cents),
                Money::decimal((int) $session->closing_difference_cents),
                $session->openedBy->auditDisplayName(),
                $session->closedBy?->auditDisplayName() ?? '',
                $session->opened_at->format('d.m.Y H:i:s'),
                $session->closed_at?->format('d.m.Y H:i:s') ?? '',
                $session->closing_comment ?? '',
                '',
                '',
                '',
                '',
                '',
                Money::decimal((int) $session->cash_sales_cents),
                '',
                Money::decimal((int) $session->cash_refunds_cents),
                'Kassenschicht nach Abschlussdatum',
            ];
        }

        return $this->csv->export([
            'Typ',
            'Berichtstag',
            'Kasse / Bereich',
            'Bezeichnung',
            'Anzahl',
            'Umsatz EUR',
            'Startbestand EUR',
            'Barumsatz EUR',
            'Einlagen EUR',
            'Entnahmen EUR',
            'Soll EUR',
            'Gezählt EUR',
            'Differenz EUR',
            'Geöffnet von',
            'Geschlossen von',
            'Geöffnet am',
            'Geschlossen am',
            'Kommentar',
            'Kategorie',
            'Brutto EUR',
            'Rabatt EUR',
            'Storno EUR',
            'Netto EUR',
            'Bar EUR',
            'PayPal EUR',
            'Barauszahlung EUR',
            'Hinweis',
        ], $rows);
    }
}
