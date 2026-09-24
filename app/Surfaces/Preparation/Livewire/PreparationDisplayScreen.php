<?php

namespace App\Surfaces\Preparation\Livewire;

use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Actions\AdvancePreparationTaskAction;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Models\PreparationTask;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.preparation')]
final class PreparationDisplayScreen extends Component
{
    #[Url(as: 'station')]
    public ?string $stationCode = null;

    public int $lastNewTaskCount = 0;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::PreparationAccess->value);
    }

    public function mount(): void
    {
        $this->lastNewTaskCount = $this->currentNewTaskCount();
    }

    public function chooseStation(string $code): void
    {
        PreparationStation::query()->where('active', true)->where('code', $code)->firstOrFail();
        $this->stationCode = $code;
        $this->lastNewTaskCount = $this->currentNewTaskCount();
        $this->screenError = null;
    }

    public function showStationSelection(): void
    {
        $this->stationCode = null;
        $this->lastNewTaskCount = 0;
        $this->screenError = null;
    }

    public function startTask(int $taskId, AdvancePreparationTaskAction $action): void
    {
        $this->advance($taskId, PreparationTaskStatus::InPreparation, $action);
    }

    public function readyTask(int $taskId, AdvancePreparationTaskAction $action): void
    {
        $this->advance($taskId, PreparationTaskStatus::Ready, $action);
    }

    public function pollRefresh(): void
    {
        $count = $this->currentNewTaskCount();
        $station = $this->selectedStation();

        if (
            $station instanceof PreparationStation
            && $count > $this->lastNewTaskCount
            && $station->notification_sound !== PreparationNotificationSound::None
        ) {
            $this->dispatch('preparation-new-work', sound: $station->notification_sound->value);
        }

        $this->lastNewTaskCount = $count;
    }

    public function render(): View
    {
        $stations = PreparationStation::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $station = $this->selectedStation();

        if (! $station instanceof PreparationStation) {
            return view('surfaces.preparation.display', [
                'stations' => $stations,
                'station' => null,
                'orders' => collect(),
                'stationTasksByItem' => collect(),
                'allTasksByOrder' => collect(),
                'orderStatuses' => [],
            ]);
        }

        $stationTasks = PreparationTask::query()
            ->with(['component', 'item'])
            ->where('station_id', $station->id)
            ->whereHas('order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $orderIds = $stationTasks->pluck('hospitality_order_id')->unique()->values();
        $orders = HospitalityOrder::query()
            ->with(['items.options', 'items.components'])
            ->whereIn('id', $orderIds)
            ->orderBy('opened_at')
            ->get();
        $allTasks = PreparationTask::query()
            ->with('station')
            ->whereIn('hospitality_order_id', $orderIds)
            ->orderBy('id')
            ->get();
        $allTasksByOrder = $allTasks->groupBy('hospitality_order_id');
        $orderStatuses = [];

        foreach ($allTasksByOrder as $orderId => $tasks) {
            $orderStatuses[(int) $orderId] = $this->overallStatus($tasks);
        }

        return view('surfaces.preparation.display', [
            'stations' => $stations,
            'station' => $station,
            'orders' => $orders,
            'stationTasksByItem' => $stationTasks->groupBy('hospitality_order_item_id'),
            'allTasksByOrder' => $allTasksByOrder,
            'orderStatuses' => $orderStatuses,
        ]);
    }

    private function advance(
        int $taskId,
        PreparationTaskStatus $target,
        AdvancePreparationTaskAction $action,
    ): void {
        $this->screenError = null;

        try {
            $station = $this->selectedStation();

            if (! $station instanceof PreparationStation) {
                throw new LogicException('Bitte zuerst eine Station auswählen.');
            }

            $task = PreparationTask::query()
                ->where('station_id', $station->id)
                ->findOrFail($taskId);

            $action->execute($task, $target, $this->currentUser());
        } catch (Throwable $exception) {
            $this->screenError = $exception instanceof LogicException
                ? $exception->getMessage()
                : 'Der Stationsstatus konnte nicht geändert werden.';

            if (! $exception instanceof LogicException) {
                report($exception);
            }
        }
    }

    private function selectedStation(): ?PreparationStation
    {
        if ($this->stationCode === null || $this->stationCode === '') {
            return null;
        }

        return PreparationStation::query()
            ->where('active', true)
            ->where('code', $this->stationCode)
            ->first();
    }

    private function currentNewTaskCount(): int
    {
        $station = $this->selectedStation();

        if (! $station instanceof PreparationStation) {
            return 0;
        }

        return PreparationTask::query()
            ->where('station_id', $station->id)
            ->where('status', PreparationTaskStatus::New->value)
            ->whereHas('order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->count();
    }

    /** @param Collection<int, PreparationTask> $tasks */
    private function overallStatus(Collection $tasks): string
    {
        if ($tasks->isNotEmpty() && $tasks->every(fn (PreparationTask $task) => $task->status === PreparationTaskStatus::Ready)) {
            return 'Fertig';
        }

        if ($tasks->every(fn (PreparationTask $task) => $task->status === PreparationTaskStatus::New)) {
            return 'Neu';
        }

        return 'In Zubereitung';
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }
}
