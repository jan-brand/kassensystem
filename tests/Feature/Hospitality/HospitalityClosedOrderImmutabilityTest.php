<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\CloseHospitalityOrderAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps closed hospitality orders and their items immutable', function () {
    $user = app(CreateUserAction::class)->execute(
        'hospitality-immutable',
        '123456',
        'Hanna',
        'Service',
    );
    $category = app(CreateCategoryAction::class)->execute('Schulbedarf');
    $product = app(CreateProductAction::class)->execute($category, 'Block', 'Block', 250);
    $area = app(CreateDiningAreaAction::class)->execute('Ausgabe');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 1');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $user);
    $order = app(AddProductToHospitalityOrderAction::class)->execute($order, $product);
    $item = $order->items->sole();

    $closed = app(CloseHospitalityOrderAction::class)->execute($order, $user);

    expect(fn () => $closed->update(['note' => 'geändert']))
        ->toThrow(LogicException::class);

    expect(fn () => $item->update(['quantity' => 2]))
        ->toThrow(LogicException::class);

    expect(fn () => $item->delete())
        ->toThrow(LogicException::class);
});
