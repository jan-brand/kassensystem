<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddMenuToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\AddProductToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Preparation\Actions\AdvancePreparationTaskAction;
use App\Modules\Preparation\Actions\CreatePreparationStationAction;
use App\Modules\Preparation\Actions\SetProductStationAssignmentAction;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationTask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets stations progress independently and keeps duplicate transitions consistent', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Speisen');
    $product = app(CreateProductAction::class)->execute($category, 'Burger', 'Burger', 850);
    $kitchen = app(CreatePreparationStationAction::class)->execute('Küche', 'kueche', PreparationNotificationSound::Bell, actor: $waiter);
    $bar = app(CreatePreparationStationAction::class)->execute('Bar', 'bar', PreparationNotificationSound::Chime, actor: $waiter);

    app(SetProductStationAssignmentAction::class)->execute($kitchen, $product, true, $waiter);
    app(SetProductStationAssignmentAction::class)->execute($bar, $product, true, $waiter);

    $area = app(CreateDiningAreaAction::class)->execute('Innen');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 1');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    app(AddProductToHospitalityOrderAction::class)->execute($order, $product);

    $tasks = PreparationTask::query()->orderBy('station_id')->get();

    expect($tasks)->toHaveCount(2)
        ->and($tasks->every(fn (PreparationTask $task) => $task->status === PreparationTaskStatus::New))->toBeTrue();

    $kitchenTask = $tasks->firstWhere('station_id', $kitchen->id);
    $barTask = $tasks->firstWhere('station_id', $bar->id);

    expect(fn () => app(AdvancePreparationTaskAction::class)->execute(
        $barTask,
        PreparationTaskStatus::Ready,
        $waiter,
    ))->toThrow(LogicException::class, 'nicht übersprungen');

    $kitchenTask = app(AdvancePreparationTaskAction::class)->execute(
        $kitchenTask,
        PreparationTaskStatus::InPreparation,
        $waiter,
    );
    $kitchenTask = app(AdvancePreparationTaskAction::class)->execute(
        $kitchenTask,
        PreparationTaskStatus::Ready,
        $waiter,
    );
    $sameReadyTask = app(AdvancePreparationTaskAction::class)->execute(
        $kitchenTask,
        PreparationTaskStatus::Ready,
        $waiter,
    );

    expect($sameReadyTask->status)->toBe(PreparationTaskStatus::Ready)
        ->and($barTask->refresh()->status)->toBe(PreparationTaskStatus::New);
});

it('routes selected menu components by their product assignments', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-menu-waiter', '123456', 'Mira', 'Menu', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Menüteile');
    $meal = app(CreateProductAction::class)->execute($category, 'Pasta', 'Pasta', 600);
    $drink = app(CreateProductAction::class)->execute($category, 'Saft', 'Saft', 200);
    $kitchen = app(CreatePreparationStationAction::class)->execute('Küche', 'kitchen', PreparationNotificationSound::Bell, actor: $waiter);
    $bar = app(CreatePreparationStationAction::class)->execute('Bar', 'bar-menu', PreparationNotificationSound::Bell, actor: $waiter);
    app(SetProductStationAssignmentAction::class)->execute($kitchen, $meal, true, $waiter);
    app(SetProductStationAssignmentAction::class)->execute($bar, $drink, true, $waiter);

    $menu = app(CreateMenuAction::class)->execute('Mittagsmenü', 750);
    $main = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    $beverage = app(CreateMenuGroupAction::class)->execute($menu, 'Getränk', 1, 1, 20);
    app(AddProductToMenuGroupAction::class)->execute($main, $meal);
    app(AddProductToMenuGroupAction::class)->execute($beverage, $drink);

    $area = app(CreateDiningAreaAction::class)->execute('Saal');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 2');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    app(AddMenuToHospitalityOrderAction::class)->execute(
        $order,
        $menu,
        [$main->id => [$meal->id], $beverage->id => [$drink->id]],
    );

    $tasks = PreparationTask::query()->with('component')->get();

    expect($tasks)->toHaveCount(2)
        ->and($tasks->firstWhere('station_id', $kitchen->id)?->component?->product_id)->toBe($meal->id)
        ->and($tasks->firstWhere('station_id', $bar->id)?->component?->product_id)->toBe($drink->id);
});

it('allows changes before preparation starts and freezes the affected item afterwards', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-lock-waiter', '123456', 'Lena', 'Lock', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Warm');
    $soup = app(CreateProductAction::class)->execute($category, 'Suppe', 'Suppe', 400);
    $toast = app(CreateProductAction::class)->execute($category, 'Toast', 'Toast', 300);
    $station = app(CreatePreparationStationAction::class)->execute('Küche', 'lock-kitchen', PreparationNotificationSound::None, actor: $waiter);
    app(SetProductStationAssignmentAction::class)->execute($station, $soup, true, $waiter);
    app(SetProductStationAssignmentAction::class)->execute($station, $toast, true, $waiter);

    $area = app(CreateDiningAreaAction::class)->execute('Bistro');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 3');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    $order = app(AddProductToHospitalityOrderAction::class)->execute($order, $soup, note: 'normal');
    $item = $order->items()->sole();

    $item->update(['note' => 'ohne Sahne', 'quantity' => 2, 'total_cents' => 800]);
    $task = PreparationTask::query()->sole();

    expect($task->refresh()->note_snapshot)->toBe('ohne Sahne')
        ->and($task->quantity)->toBe(2);

    app(AdvancePreparationTaskAction::class)->execute($task, PreparationTaskStatus::InPreparation, $waiter);

    expect(fn () => $item->refresh()->update(['note' => 'doch normal']))
        ->toThrow(LogicException::class, 'bereits in Zubereitung')
        ->and(fn () => $item->refresh()->delete())
        ->toThrow(LogicException::class, 'bereits in Zubereitung');

    app(AddProductToHospitalityOrderAction::class)->execute($order->refresh(), $toast);

    expect(PreparationTask::query()->count())->toBe(2);
});

it('backfills open work when a product is assigned to an active station later', function () {
    $waiter = app(CreateUserAction::class)->execute('prep-backfill-waiter', '123456', 'Berta', 'Backfill', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Getränke');
    $water = app(CreateProductAction::class)->execute($category, 'Wasser', 'Wasser', 150);
    $station = app(CreatePreparationStationAction::class)->execute('Bar', 'late-bar', PreparationNotificationSound::Bell, actor: $waiter);
    $area = app(CreateDiningAreaAction::class)->execute('Terrasse');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 4');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);

    app(AddProductToHospitalityOrderAction::class)->execute($order, $water);
    expect(PreparationTask::query()->count())->toBe(0);

    app(SetProductStationAssignmentAction::class)->execute($station, $water, true, $waiter);

    expect(PreparationTask::query()->count())->toBe(1)
        ->and(PreparationTask::query()->sole()->status)->toBe(PreparationTaskStatus::New);
});

it('defines no served preparation status in v2', function () {
    expect(array_map(
        static fn (PreparationTaskStatus $status): string => $status->value,
        PreparationTaskStatus::cases(),
    ))->toBe(['new', 'in_preparation', 'ready']);
});
