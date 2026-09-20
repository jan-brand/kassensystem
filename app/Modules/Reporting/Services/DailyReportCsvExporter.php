<?php

namespace App\Modules\Reporting\Services;

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Models\CashSession;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;

final class DailyReportCsvExporter
{
    public function __construct(private readonly CsvExporter $csv) {}

    /**
     * @param array{
     *     date: string,
     *     sales_count: int,
     *     revenue_cents: int,
     *     free_sales_count: int,
     *     products: list<array{product_id: int, product_name: string, quantity: int, revenue_cents: int}>
     * } $summary
     * @param Collection<int, CashSession> $sessions
     */
    public function export(array $summary, Collection $sessions): string
    {
        $rows = [[
            'Übersicht',
            $summary['date'],
            '',
            'Tagesumsatz',
            $summary['sales_count'],
            Money::decimal($summary['revenue_cents']),
            '', '', '', '', '', '', '', '', '', '', '', '',
        ], [
            'Übersicht',
            $summary['date'],
            '',
            'Kostenlose Verkäufe',
            $summary['free_sales_count'],
            '0,00',
            '', '', '', '', '', '', '', '', '', '', '', '',
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
                $session->openedBy?->auditDisplayName() ?? '',
                $session->closedBy?->auditDisplayName() ?? '',
                $session->opened_at?->format('d.m.Y H:i:s') ?? '',
                $session->closed_at?->format('d.m.Y H:i:s') ?? '',
                $session->closing_comment ?? '',
            ];
        }

        return $this->csv->export([
            'Typ',
            'Berichtstag',
            'Kasse',
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
        ], $rows);
    }
}
