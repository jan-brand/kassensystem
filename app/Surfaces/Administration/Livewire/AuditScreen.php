<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Queries\SearchAuditEventsQuery;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('layouts.administration')]
final class AuditScreen extends Component
{
    use WithPagination;

    public string $eventKey = '';

    public string $actor = '';

    public string $subjectType = '';

    public string $subjectId = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public ?int $selectedEventId = null;

    public function boot(): void
    {
        $user = Auth::user();

        if (
            ! $user instanceof User
            || ! $user->active
            || $user->role !== UserRole::Administrator
        ) {
            abort(403);
        }
    }

    public function applyFilters(): void
    {
        $this->validate([
            'eventKey' => ['nullable', 'string', 'max:160'],
            'actor' => ['nullable', 'string', 'max:160'],
            'subjectType' => ['nullable', 'string', 'max:255'],
            'subjectId' => ['nullable', 'integer', 'min:1'],
            'dateFrom' => ['nullable', 'date_format:Y-m-d'],
            'dateTo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
        ], [
            'subjectId.integer' => 'Die Subject-ID muss eine Zahl sein.',
            'subjectId.min' => 'Die Subject-ID muss mindestens 1 sein.',
            'dateFrom.date_format' => 'Das Von-Datum ist ungültig.',
            'dateTo.date_format' => 'Das Bis-Datum ist ungültig.',
            'dateTo.after_or_equal' => 'Das Bis-Datum darf nicht vor dem Von-Datum liegen.',
        ]);

        $this->resetPage();
        $this->selectedEventId = null;
    }

    public function resetFilters(): void
    {
        $this->reset([
            'eventKey',
            'actor',
            'subjectType',
            'subjectId',
            'dateFrom',
            'dateTo',
            'selectedEventId',
        ]);
        $this->resetValidation();
        $this->resetPage();
    }

    public function showEvent(int $eventId): void
    {
        $event = AuditEvent::query()->findOrFail($eventId);
        $this->selectedEventId = $event->id;
    }

    public function closeEvent(): void
    {
        $this->selectedEventId = null;
    }

    public function render(SearchAuditEventsQuery $query): View
    {
        $from = $this->parseDate($this->dateFrom)?->startOfDay();
        $to = $this->parseDate($this->dateTo)?->endOfDay();
        $subjectId = ctype_digit($this->subjectId) ? (int) $this->subjectId : null;
        $selectedEvent = $this->selectedEventId !== null
            ? AuditEvent::query()->find($this->selectedEventId)
            : null;

        return view('surfaces.administration.audit', [
            'events' => $query->execute(
                eventKey: $this->eventKey,
                actor: $this->actor,
                subjectType: $this->subjectType,
                subjectId: $subjectId,
                from: $from,
                to: $to,
            ),
            'selectedEvent' => $selectedEvent,
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

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date;
    }
}
