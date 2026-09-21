<?php

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Surfaces\Pos\Livewire\LoginScreen;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers the manifest driven pos routes and protects the register page', function () {
    expect(Route::has('login'))->toBeTrue()
        ->and(Route::has('pos.register'))->toBeTrue();

    $this->get(route('pos.register'))
        ->assertRedirect(route('login'));
});

it('logs in with username and pin', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'kasse',
        pin: '123456',
        firstName: 'Klara',
        lastName: 'Kasse',
        role: UserRole::Cashier,
    );

    Livewire::test(LoginScreen::class)
        ->set('username', 'kasse')
        ->set('pin', '123456')
        ->call('login')
        ->assertRedirect(route('pos.register'));

    $this->assertAuthenticatedAs($user);
});

it('opens the register, builds a cart and completes a cash sale', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'cashier',
        pin: '123456',
        firstName: 'Caro',
        lastName: 'Cashier',
        role: UserRole::Cashier,
    );

    $category = Category::query()->create([
        'name' => 'Getränke',
        'active' => true,
        'sort_order' => 10,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Apfelschorle 0,5l',
        'short_name' => 'Apfelschorle',
        'price_cents' => 150,
        'active' => true,
        'sort_order' => 10,
    ]);

    $this->actingAs($user);

    $component = Livewire::test(RegisterScreen::class)
        ->assertSet('openingCash', '')
        ->set('openingCash', '20,00')
        ->call('openCashSession')
        ->assertSet('screenError', null)
        ->call('addProduct', $product->id)
        ->call('addProduct', $product->id)
        ->assertSet('screenError', null);

    $sale = Sale::query()->with('items')->firstOrFail();

    expect($sale->status)->toBe(SaleStatus::Open)
        ->and($sale->items)->toHaveCount(1)
        ->and($sale->items->first()->quantity)->toBe(2)
        ->and($sale->total_cents)->toBe(300);

    $component
        ->call('showPayment')
        ->assertSet('receivedAmount', '')
        ->set('receivedAmount', '5,00')
        ->call('completeSale')
        ->assertSet('screenError', null)
        ->assertSet('lastChangeCents', 200);

    $sale->refresh();

    expect($sale->status)->toBe(SaleStatus::Completed)
        ->and($sale->number)->not->toBeNull()
        ->and(Payment::query()->where('sale_id', $sale->id)->value('change_cents'))->toBe(200);
});

it('blocks user switching while a cart is open', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'switch-test',
        pin: '123456',
        firstName: 'Sina',
        lastName: 'Switch',
    );

    $category = Category::query()->create([
        'name' => 'Snacks',
        'active' => true,
        'sort_order' => 10,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Müsliriegel',
        'short_name' => 'Riegel',
        'price_cents' => 100,
        'active' => true,
        'sort_order' => 10,
    ]);

    $this->actingAs($user);

    Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('switchUser')
        ->assertSet('screenError', 'Vor dem Benutzerwechsel muss der offene Warenkorb abgeschlossen oder verworfen werden.');

    $this->assertAuthenticatedAs($user);
});

it('does not leave an empty sale when adding an unavailable product fails', function () {
    $user = app(CreateUserAction::class)->execute(
        username: 'rollback-test',
        pin: '123456',
        firstName: 'Rita',
        lastName: 'Rollback',
    );

    $category = Category::query()->create([
        'name' => 'Inaktiv-Test',
        'active' => true,
        'sort_order' => 10,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Nicht verfügbar',
        'short_name' => 'Inaktiv',
        'price_cents' => 100,
        'active' => false,
        'sort_order' => 10,
    ]);

    $this->actingAs($user);

    Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id);

    expect(Sale::query()->count())->toBe(0);
});
