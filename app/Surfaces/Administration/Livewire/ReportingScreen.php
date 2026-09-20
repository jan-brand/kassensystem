<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Reporting\Queries\GetCashSessionReportQuery;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Reporting\Services\DailyReportCsvExporter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.administration')]
final class ReportingScreen extends Component
{
    public string $date = '';

    public string $reportDate = '';

    public function boot(): void
    {
        $user = Auth::user();

        if (
            ! $user instanceof User
            || ! $user->active
            || ! in_array($user->role, [UserRole::Manager, UserRole::Administrator], true)
        ) {
            abort(403);
        }
    }

    public function mount(): void
    {
        $today = $this->todayDate();
        $this->date = $today;
        $this->reportDate = $today;
    }

    public function applyDate(): void
    {
        $this->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ], [
            'date.required' => 'Bitte ein Datum auswählen.',
            'date.date_format' => 'Das Datum ist ungültig.',
        ]);

        $this->reportDate = $this->date;
    }

    public function previousDay(): void
    {
        $this->moveDay(-1);
    }

    public function nextDay(): void
    {
        $this->moveDay(1);
    }

    public function today(): void
    {
        $today = $this->todayDate();
        $this->date = $today;
        $this->reportDate = $today;
        $this->resetValidation();
    }

    public function downloadCsv(
        GetDailySummaryQuery $summaryQuery,
        GetCashSessionReportQuery $sessionQuery,
        DailyReportCsvExporter $exporter,
    ): StreamedResponse {
        $summary = $summaryQuery->execute($this->reportDate);
        $sessions = $sessionQuery->execute($this->reportDate);
        $csv = $exporter->export($summary, $sessions);

        return response()->streamDownload(
            static fn () => print($csv),
            'kassenbericht-'.$this->reportDate.'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function render(
        GetDailySummaryQuery $summaryQuery,
        GetCashSessionReportQuery $sessionQuery,
    ): View {
        return view('surfaces.administration.reporting', [
            'summary' => $summaryQuery->execute($this->reportDate),
            'sessions' => $sessionQuery->execute($this->reportDate),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
            'timezone' => (string) config('kassensystem.timezone', 'Europe/Berlin'),
        ]);
    }

    private function moveDay(int $days): void
    {
        $timezone = (string) config('kassensystem.timezone', 'Europe/Berlin');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $this->reportDate, $timezone);

        if ($day === false) {
            $day = CarbonImmutable::now($timezone)->startOfDay();
        }

        $target = $day->addDays($days)->format('Y-m-d');
        $this->date = $target;
        $this->reportDate = $target;
        $this->resetValidation();
    }

    private function todayDate(): string
    {
        return CarbonImmutable::now((string) config('kassensystem.timezone', 'Europe/Berlin'))
            ->format('Y-m-d');
    }
}
