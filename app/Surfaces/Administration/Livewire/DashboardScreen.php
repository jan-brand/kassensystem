<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.administration')]
final class DashboardScreen extends Component
{
    public function render(GetDailySummaryQuery $dailySummary): View
    {
        $date = CarbonImmutable::now((string) config('kassensystem.timezone', 'Europe/Berlin'))
            ->format('Y-m-d');

        return view('surfaces.administration.dashboard', [
            'date' => $date,
            'summary' => $dailySummary->execute($date),
            'activeProducts' => Product::query()->where('active', true)->count(),
            'cashiers' => User::query()->where('role', UserRole::Cashier->value)->count(),
        ]);
    }
}
