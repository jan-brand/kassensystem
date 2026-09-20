<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Reporting\Queries\GetCashSessionReportQuery;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Reporting\Services\CsvExporter;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.administration')]
final class ReportingScreen extends Component
{
    public string $date = '';

    public function mount(): void
    {
        $this->date = CarbonImmutable::now((string) config('kassensystem.timezone', 'Europe/Berlin'))
            ->format('Y-m-d');
    }

    public function downloadCsv(
        GetDailySummaryQuery $summaryQuery,
        GetCashSessionReportQuery $sessionQuery,
        CsvExporter $exporter,
    ): StreamedResponse {
        $summary = $summaryQuery->execute($this->date);
        $sessions = $sessionQuery->execute($this->date);

        $rows = [
            ['Übersicht', 'Verkäufe', $summary['sales_count'], Money::decimal($summary['revenue_cents']), '', '', ''],
        ];

        foreach ($summary['products'] as $product) {
            $rows[] = [
                'Produkt',
                $product['product_name'],
                $product['quantity'],
                Money::decimal($product['revenue_cents']),
                '',
                '',
                '',
            ];
        }

        foreach ($sessions as $session) {
            $rows[] = [
                'Kassenschicht',
                $session->register->name,
                '',
                '',
                Money::decimal((int) $session->closing_expected_cash_cents),
                Money::decimal((int) $session->closing_counted_cash_cents),
                Money::decimal((int) $session->closing_difference_cents),
            ];
        }

        $csv = $exporter->export(
            ['Typ', 'Bezeichnung', 'Anzahl', 'Umsatz EUR', 'Soll EUR', 'Gezählt EUR', 'Differenz EUR'],
            $rows,
        );

        return response()->streamDownload(
            static fn () => print($csv),
            'kassenbericht-'.$this->date.'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function render(
        GetDailySummaryQuery $summaryQuery,
        GetCashSessionReportQuery $sessionQuery,
    ): View {
        return view('surfaces.administration.reporting', [
            'summary' => $summaryQuery->execute($this->date),
            'sessions' => $sessionQuery->execute($this->date),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
        ]);
    }
}
