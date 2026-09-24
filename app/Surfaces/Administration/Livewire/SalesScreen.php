<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Queries\GetCompletedSaleQuery;
use App\Modules\Sales\Queries\SearchCompletedSalesQuery;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class SalesScreen extends Component
{
    use WithPagination;

    public string $number = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $selectedSaleId = null;

    public string $reversalReason = '';

    public ?string $notice = null;

    public function boot(): void
    {
        Gate::authorize(Permission::SalesView->value);
    }

    public function applyFilters(): void
    {
        $this->validate([
            'number' => ['nullable', 'string', 'max:32'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
        ], [
            'number.max' => 'Die Verkaufsnummer ist zu lang.',
            'dateFrom.date_format' => 'Das Von-Datum ist ungültig.',
            'dateTo.date_format' => 'Das Bis-Datum ist ungültig.',
            'dateTo.after_or_equal' => 'Das Bis-Datum darf nicht vor dem Von-Datum liegen.',
        ]);

        $this->reset(['selectedSaleId', 'reversalReason', 'notice']);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset([
            'number',
            'dateFrom',
            'dateTo',
            'selectedSaleId',
            'reversalReason',
            'notice',
        ]);
        $this->resetValidation();
        $this->resetPage();
    }

    public function showSale(int $saleId, GetCompletedSaleQuery $query): void
    {
        $sale = $query->execute($saleId);

        $this->selectedSaleId = $sale->id;
        $this->reversalReason = '';
        $this->notice = null;
        $this->resetValidation();
    }

    public function closeSale(): void
    {
        $this->reset(['selectedSaleId', 'reversalReason', 'notice']);
        $this->resetValidation();
    }

    public function reverseSelectedSale(
        GetCompletedSaleQuery $query,
        ReverseCompletedSaleAction $reverse,
    ): void {
        Gate::authorize(Permission::SalesReverse->value);

        $this->validate([
            'reversalReason' => ['required', 'string', 'min:3', 'max:500'],
        ], [
            'reversalReason.required' => 'Bitte einen Stornogrund angeben.',
            'reversalReason.min' => 'Der Stornogrund muss mindestens 3 Zeichen lang sein.',
            'reversalReason.max' => 'Der Stornogrund darf maximal 500 Zeichen lang sein.',
        ]);

        if ($this->selectedSaleId === null) {
            return;
        }

        $actor = auth()->user();

        if (! $actor instanceof User) {
            abort(403);
        }

        $sale = $query->execute($this->selectedSaleId);

        try {
            $reverse->execute(
                sale: $sale,
                actor: $actor,
                reason: $this->reversalReason,
            );
        } catch (InvalidArgumentException|LogicException $exception) {
            $this->addError('reversalReason', $exception->getMessage());

            return;
        }

        $this->notice = "Verkauf {$sale->number} wurde vollständig storniert.";
        $this->reversalReason = '';
        $this->resetValidation('reversalReason');
    }

    public function render(
        SearchCompletedSalesQuery $search,
        GetCompletedSaleQuery $saleQuery,
        GetSystemSettingsQuery $settingsQuery,
    ): View {
        $from = $this->parseDate($this->dateFrom)?->startOfDay();
        $to = $this->parseDate($this->dateTo)?->endOfDay();
        $selectedSale = $this->selectedSaleId !== null
            ? $saleQuery->execute($this->selectedSaleId)
            : null;
        $settings = $settingsQuery->execute();

        return view('surfaces.administration.sales', [
            'sales' => $search->execute(
                number: $this->number,
                from: $from,
                to: $to,
            ),
            'selectedSale' => $selectedSale,
            'canReverseSale' => Gate::allows(Permission::SalesReverse->value),
            'cafeteriaName' => $settings?->cafeteria_name ?: (string) config('app.name'),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
        ]);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return null;
        }

        try {
            $timezone = (string) config('kassensystem.timezone', 'Europe/Berlin');
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        } catch (Throwable) {
            return null;
        }

        if ($date === null || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }
}
