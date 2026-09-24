<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Preparation\Actions\AdvancePreparationTaskAction;
use App\Modules\Preparation\Actions\CreatePreparationStationAction;
use App\Modules\Preparation\Actions\SetProductStationAssignmentAction;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationTask;
use App\Surfaces\Preparation\Livewire\PreparationDisplayScreen;
use App\Surfaces\Waiter\Livewire\WaiterPreparationScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('protects preparation routes by role', function () {
    $cashier = app(CreateUserAction::class)->execute('prep-cashier', '123456', 'Casey', 'Cashier');
    $waiter = app(CreateUserAction::class)->execute('prep-route-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $manager = app(CreateUserAction::class)->execute('prep-manager', '123456', 'Mara', 'Manager', UserRole::Manager);

    $this->actingAs($cashier)->get(route('preparation.display'))->assertForbidden();
    $this->actingAs($cashier)->get(route('waiter.preparation'))->assertForbidden();
    $this->actingAs($cashier)->get(route('administration.preparation'))->assertForbidden();

    $this->actingAs($waiter)->get(route('preparation.display'))->assertOk();
    $this->actingAs($waiter)->get(route('waiter.preparation'))->assertOk();
    $this->actingAs($waiter)->get(route('administration.preparation'))->assertForbidden();

    $this->actingAs($manager)->get(route('administration.preparation'))->assertOk();
});

it('shows the whole order while highlighting work for the selected station', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-display-waiter', '123456', 'Dora', 'Display', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Display');
    $soup = app(CreateProductAction::class)->execute($category, 'Suppe', 'Suppe', 400);
    $water = app(CreateProductAction::class)->execute($category, 'Wasser', 'Wasser', 150);
    $kitchen = app(CreatePreparationStationAction::class)->execute('Küche', 'display-kitchen', PreparationNotificationSound::Bell, actor: $waiter);
    app(SetProductStationAssignmentAction::class)->execute($kitchen, $soup, true, $waiter);

    $area = app(CreateDiningAreaAction::class)->execute('Innenraum');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 7');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    $order = app(AddProductToHospitalityOrderAction::class)->execute($order, $soup);
    app(AddProductToHospitalityOrderAction::class)->execute($order, $water);

    $this->actingAs($waiter);

    Livewire::test(PreparationDisplayScreen::class)
        ->call('chooseStation', $kitchen->code)
        ->assertSee('Tisch 7')
        ->assertSee('Suppe')
        ->assertSee('Wasser')
        ->assertSee('Starten');
});

it('shows ready station parts in the waiter overview without a served state', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-ready-waiter', '123456', 'Rita', 'Ready', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Ready');
    $drink = app(CreateProductAction::class)->execute($category, 'Limo', 'Limo', 250);
    $bar = app(CreatePreparationStationAction::class)->execute('Bar', 'ready-bar', PreparationNotificationSound::Chime, actor: $waiter);
    app(SetProductStationAssignmentAction::class)->execute($bar, $drink, true, $waiter);
    $area = app(CreateDiningAreaAction::class)->execute('Lounge');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 9');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    app(AddProductToHospitalityOrderAction::class)->execute($order, $drink);

    $task = PreparationTask::query()->sole();
    $task = app(AdvancePreparationTaskAction::class)->execute($task, PreparationTaskStatus::InPreparation, $waiter);
    app(AdvancePreparationTaskAction::class)->execute($task, PreparationTaskStatus::Ready, $waiter);

    $this->actingAs($waiter);

    Livewire::test(WaiterPreparationScreen::class)
        ->assertSee('Tisch 9')
        ->assertSee('Limo')
        ->assertSee('Bar');
});
