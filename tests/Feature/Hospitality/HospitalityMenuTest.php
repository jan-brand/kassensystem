<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddMenuToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('enforces menu slot rules and stores component snapshots', function () {
    $user = app(CreateUserAction::class)->execute(
        'hospitality-menu',
        '123456',
        'Hanna',
        'Service',
    );
    $category = app(CreateCategoryAction::class)->execute('Hauptgerichte');
    $pasta = app(CreateProductAction::class)->execute($category, 'Pasta', 'Pasta', 500);
    $curry = app(CreateProductAction::class)->execute($category, 'Curry', 'Curry', 550);

    $menu = app(CreateMenuAction::class)->execute('Tagesmenü', 600);
    $main = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($main, $pasta, 0, 10);
    $curryEntry = app(AddProductToMenuGroupAction::class)->execute($main, $curry, 100, 20);

    $area = app(CreateDiningAreaAction::class)->execute('Mensa');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 3');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $user);

    expect(fn () => app(AddMenuToHospitalityOrderAction::class)->execute(
        $order,
        $menu,
        [],
    ))->toThrow(LogicException::class, 'nicht vollständig');

    expect(fn () => app(AddMenuToHospitalityOrderAction::class)->execute(
        $order,
        $menu,
        [$main->id => [$pasta->id, $curry->id]],
    ))->toThrow(LogicException::class, 'nicht vollständig');

    $order = app(AddMenuToHospitalityOrderAction::class)->execute(
        $order,
        $menu,
        [$main->id => [$curry->id]],
        1,
        'scharf',
    );

    $item = $order->items->sole();

    expect($item->label)->toBe('Tagesmenü')
        ->and($item->unit_price_cents)->toBe(700)
        ->and($item->total_cents)->toBe(700)
        ->and($item->is_consumable)->toBeTrue()
        ->and($item->note)->toBe('scharf')
        ->and($item->components)->toHaveCount(1)
        ->and($item->components->first()->menu_group_name)->toBe('Hauptgericht')
        ->and($item->components->first()->product_name)->toBe('Curry')
        ->and($item->components->first()->price_delta_cents)->toBe(100);

    $curry->update(['name' => 'Curry Neu']);
    $curryEntry->update(['price_delta_cents' => 300]);

    $item->refresh()->load('components');

    expect($item->unit_price_cents)->toBe(700)
        ->and($item->components->first()->product_name)->toBe('Curry')
        ->and($item->components->first()->price_delta_cents)->toBe(100);
});
