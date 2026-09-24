<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Actions\CreatePreparationStationAction;
use App\Modules\Preparation\Actions\SetProductStationAssignmentAction;
use App\Modules\Preparation\Actions\UpdatePreparationStationAction;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Models\PreparationStation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.administration')]
final class PreparationConfigurationScreen extends Component
{
    public string $stationName = '';

    public string $stationCode = '';

    public int $stationSortOrder = 0;

    public string $stationSound = 'bell';

    public ?int $assignmentStationId = null;

    public ?int $assignmentProductId = null;

    /** @var array<int, string> */
    public array $stationNames = [];

    /** @var array<int, int|string> */
    public array $stationSortOrders = [];

    /** @var array<int, string> */
    public array $stationSounds = [];

    /** @var array<int, bool> */
    public array $stationActive = [];

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::PreparationManage->value);
    }

    public function mount(): void
    {
        $this->syncStationForms();
    }

    public function createStation(CreatePreparationStationAction $action): void
    {
        $this->clearMessages();

        try {
            $station = $action->execute(
                name: $this->stationName,
                code: $this->stationCode,
                sound: PreparationNotificationSound::from($this->stationSound),
                sortOrder: $this->stationSortOrder,
                actor: $this->currentUser(),
            );

            $this->stationName = '';
            $this->stationCode = '';
            $this->stationSortOrder = 0;
            $this->assignmentStationId = $station->id;
            $this->syncStationForms();
            $this->notice = 'Zubereitungsstation wurde angelegt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function saveStation(int $stationId, UpdatePreparationStationAction $action): void
    {
        $this->clearMessages();

        try {
            $action->execute(
                station: PreparationStation::query()->findOrFail($stationId),
                name: (string) ($this->stationNames[$stationId] ?? ''),
                sound: PreparationNotificationSound::from((string) ($this->stationSounds[$stationId] ?? 'bell')),
                sortOrder: (int) ($this->stationSortOrders[$stationId] ?? 0),
                active: (bool) ($this->stationActive[$stationId] ?? false),
                actor: $this->currentUser(),
            );

            $this->syncStationForms();
            $this->notice = 'Station wurde gespeichert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function assignProduct(SetProductStationAssignmentAction $action): void
    {
        $this->clearMessages();

        if ($this->assignmentStationId === null || $this->assignmentProductId === null) {
            $this->screenError = 'Bitte Station und Produkt auswählen.';

            return;
        }

        try {
            $action->execute(
                station: PreparationStation::query()->findOrFail($this->assignmentStationId),
                product: Product::query()->findOrFail($this->assignmentProductId),
                assigned: true,
                actor: $this->currentUser(),
            );

            $this->assignmentProductId = null;
            $this->notice = 'Produkt wurde der Station zugeordnet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function removeProduct(int $stationId, int $productId, SetProductStationAssignmentAction $action): void
    {
        $this->clearMessages();

        try {
            $action->execute(
                station: PreparationStation::query()->findOrFail($stationId),
                product: Product::query()->findOrFail($productId),
                assigned: false,
                actor: $this->currentUser(),
            );

            $this->notice = 'Produktzuordnung wurde für neue Bestellungen entfernt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.preparation', [
            'stations' => PreparationStation::query()
                ->with(['products.category'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'products' => Product::query()
                ->with('category')
                ->where('active', true)
                ->whereHas('category', fn ($query) => $query->where('active', true))
                ->orderBy('name')
                ->get(),
            'sounds' => PreparationNotificationSound::cases(),
        ]);
    }

    private function syncStationForms(): void
    {
        foreach (PreparationStation::query()->get() as $station) {
            $this->stationNames[$station->id] = $station->name;
            $this->stationSortOrders[$station->id] = $station->sort_order;
            $this->stationSounds[$station->id] = $station->notification_sound->value;
            $this->stationActive[$station->id] = $station->active;
        }
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function friendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof \InvalidArgumentException || $exception instanceof \LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Zubereitungskonfiguration konnte nicht gespeichert werden.';
    }
}
