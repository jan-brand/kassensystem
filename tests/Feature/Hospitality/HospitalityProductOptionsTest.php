<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\AssignOptionGroupToCategoryAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateOptionGroupAction;
use App\Modules\Hospitality\Actions\CreateOptionValueAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('snapshots product options prices and free text independently from later catalog changes', function () {
    $user = app(CreateUserAction::class)->execute(
        'hospitality-options',
        '123456',
        'Hanna',
        'Service',
    );
    $category = app(CreateCategoryAction::class)->execute('Getränke');
    $product = app(CreateProductAction::class)->execute($category, 'Apfelschorle', 'Schorle', 250);

    $size = app(CreateOptionGroupAction::class)->execute('Größe', 1, 1);
    $normal = app(CreateOptionValueAction::class)->execute($size, 'Normal', 0, 10);
    $large = app(CreateOptionValueAction::class)->execute($size, 'Groß', 50, 20);
    app(AssignOptionGroupToCategoryAction::class)->execute($size, $category);

    $area = app(CreateDiningAreaAction::class)->execute('Terrasse');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 7');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $user);

    expect(fn () => app(AddProductToHospitalityOrderAction::class)->execute(
        $order,
        $product,
        [],
    ))->toThrow(LogicException::class, 'nicht vollständig');

    expect(fn () => app(AddProductToHospitalityOrderAction::class)->execute(
        $order,
        $product,
        [$normal->id, $large->id],
    ))->toThrow(LogicException::class, 'nicht vollständig');

    $order = app(AddProductToHospitalityOrderAction::class)->execute(
        $order,
        $product,
        [$large->id],
        2,
        'ohne Eis',
    );

    $item = $order->items->sole();

    expect($item->label)->toBe('Apfelschorle')
        ->and($item->unit_price_cents)->toBe(300)
        ->and($item->quantity)->toBe(2)
        ->and($item->total_cents)->toBe(600)
        ->and($item->note)->toBe('ohne Eis')
        ->and($item->options)->toHaveCount(1)
        ->and($item->options->first()->option_group_name)->toBe('Größe')
        ->and($item->options->first()->option_value_name)->toBe('Groß')
        ->and($item->options->first()->price_delta_cents)->toBe(50)
        ->and($order->total_cents)->toBe(600);

    $product->update(['name' => 'Apfelschorle Neu', 'price_cents' => 500]);
    $large->update(['name' => 'XL', 'price_delta_cents' => 200]);

    $item->refresh()->load('options');

    expect($item->label)->toBe('Apfelschorle')
        ->and($item->unit_price_cents)->toBe(300)
        ->and($item->options->first()->option_value_name)->toBe('Groß')
        ->and($item->options->first()->price_delta_cents)->toBe(50);
});
