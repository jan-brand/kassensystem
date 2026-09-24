<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Surfaces\Waiter\Livewire\WaiterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets multiple waiters work on the same table without owning the order', function () {
    $firstWaiter = app(CreateUserAction::class)->execute(
        'waiter-pos-one',
        '123456',
        'Wanda',
        'One',
        UserRole::Waiter,
    );
    $secondWaiter = app(CreateUserAction::class)->execute(
        'waiter-pos-two',
        '123456',
        'Willi',
        'Two',
        UserRole::Waiter,
    );
    $area = app(CreateDiningAreaAction::class)->execute('Terrasse');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 4');
    $category = app(CreateCategoryAction::class)->execute('Getränke');
    $product = app(CreateProductAction::class)->execute($category, 'Wasser', 'Wasser', 200);

    $this->actingAs($firstWaiter);

    Livewire::test(WaiterScreen::class)
        ->call('selectTable', $table->id)
        ->assertSet('openingTable', true)
        ->set('orderNote', 'Geburtstag')
        ->call('openSelectedTable')
        ->assertSet('openingTable', false)
        ->call('chooseProduct', $product->id)
        ->call('addSelectedProduct')
        ->assertHasNoErrors();

    $order = HospitalityOrder::query()->sole();

    expect($order->opened_by_user_id)->toBe($firstWaiter->id)
        ->and($order->items()->count())->toBe(1)
        ->and($order->total_cents)->toBe(200);

    $this->actingAs($secondWaiter);

    Livewire::test(WaiterScreen::class)
        ->call('selectTable', $table->id)
        ->assertSet('selectedOrderId', $order->id)
        ->assertSee($order->number)
        ->assertSee('Wasser')
        ->call('chooseProduct', $product->id)
        ->call('addSelectedProduct')
        ->assertHasNoErrors();

    expect($order->refresh()->items()->count())->toBe(2)
        ->and($order->total_cents)->toBe(400);
});

it('protects the waiter route from cashier-only accounts', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'waiter-route-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $waiter = app(CreateUserAction::class)->execute(
        'waiter-route-waiter',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );

    $this->actingAs($cashier)->get(route('waiter.service'))->assertForbidden();
    $this->actingAs($waiter)->get(route('waiter.service'))->assertOk();
});
