<?php

namespace App\Surfaces\Waiter\Livewire;

use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Models\PreparationTask;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.waiter')]
final class WaiterPreparationScreen extends Component
{
    public function boot(): void
    {
        Gate::authorize(Permission::PreparationAccess->value);
    }

    public function render(): View
    {
        $readyTasks = PreparationTask::query()
            ->with(['station', 'order', 'item', 'component'])
            ->where('status', PreparationTaskStatus::Ready->value)
            ->whereHas('order', fn ($query) => $query->where('status', HospitalityOrderStatus::Open->value))
            ->orderBy('ready_at')
            ->orderBy('id')
            ->get();

        return view('surfaces.waiter.preparation', [
            'readyByOrder' => $readyTasks->groupBy('hospitality_order_id'),
            'stations' => PreparationStation::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }
}
