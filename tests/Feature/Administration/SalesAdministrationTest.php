<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Surfaces\Administration\Livewire\SalesScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('protects the sales browser and allows administration roles', function () {
    $cashier = app(CreateUserAction::class)->execute('sales-cashier-access', '123456', 'Casey', 'Cashier');
    $this->actingAs($cashier)->get(route('administration.sales'))->assertForbidden();

    $manager = app(CreateUserAction::class)->execute(
        'sales-manager-access',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $this->actingAs($manager)->get(route('administration.sales'))->assertOk();

    Livewire::test(SalesScreen::class)
        ->assertStatus(200)
        ->assertSee('Verkäufe & Belege');
});

it('filters completed sales by number and completion date', function () {
    Carbon::setTestNow('2026-09-20 10:00:00');

    $manager = app(CreateUserAction::class)->execute('sales-manager-filter', '123456', 'Mara', 'Manager', UserRole::Manager);
    $cashier = app(CreateUserAction::class)->execute('sales-cashier-filter', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create(['code' => 'sales-filter', 'name' => 'Hauptkasse', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 150);

    $first = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $first = app(AddProductToSaleAction::class)->execute($first, $product);
    $first = app(CompleteCashSaleAction::class)->execute($first, 200);

    Carbon::setTestNow('2026-09-21 10:00:00');
    $second = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $second = app(AddProductToSaleAction::class)->execute($second, $product);
    $second = app(CompleteCashSaleAction::class)->execute($second, 200);

    $this->actingAs($manager);

    Livewire::test(SalesScreen::class)
        ->set('number', (string) $first->number)
        ->set('dateFrom', '2026-09-20')
        ->set('dateTo', '2026-09-20')
        ->call('applyFilters')
        ->assertHasNoErrors()
        ->assertSee((string) $first->number)
        ->assertDontSee((string) $second->number)
        ->assertSee('1 Treffer');
});

it('shows immutable item snapshots and cash payment data on the receipt', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $manager = app(CreateUserAction::class)->execute('sales-manager-receipt', '123456', 'Mara', 'Manager', UserRole::Manager);
    $cashier = app(CreateUserAction::class)->execute('sales-cashier-receipt', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create(['code' => 'sales-receipt', 'name' => 'Kasse 1', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel Original', 'Brezel', 150);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteCashSaleAction::class)->execute($sale, 500);

    $product->update(['name' => 'Brezel Neu', 'price_cents' => 250]);

    $this->actingAs($manager);

    Livewire::test(SalesScreen::class)
        ->call('showSale', $sale->id)
        ->assertSee((string) $sale->number)
        ->assertSee('Brezel Original')
        ->assertDontSee('Brezel Neu')
        ->assertSee('2 × 1,50 €')
        ->assertSee('3,00 €')
        ->assertSee('5,00 €')
        ->assertSee('2,00 €')
        ->assertSee('Gespeicherter Verkaufsbeleg');
});

it('renders zero euro sales without inventing a payment and validates date ranges', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $manager = app(CreateUserAction::class)->execute('sales-manager-free', '123456', 'Mara', 'Manager', UserRole::Manager);
    $cashier = app(CreateUserAction::class)->execute('sales-cashier-free', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create(['code' => 'sales-free', 'name' => 'Kasse 1', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Getränke');
    $product = app(CreateProductAction::class)->execute($category, 'Wasser gratis', 'Wasser', 0);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteCashSaleAction::class)->execute($sale, 0);

    expect($sale->payment)->toBeNull();

    $this->actingAs($manager);

    Livewire::test(SalesScreen::class)
        ->call('showSale', $sale->id)
        ->assertSee('Kostenloser Verkauf · keine Zahlung')
        ->assertSee('0,00 €')
        ->set('dateFrom', '2026-09-21')
        ->set('dateTo', '2026-09-20')
        ->call('applyFilters')
        ->assertHasErrors(['dateTo' => 'after_or_equal']);
});
