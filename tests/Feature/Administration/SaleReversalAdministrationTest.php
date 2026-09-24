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
use App\Modules\Sales\Models\SaleReversal;
use App\Surfaces\Administration\Livewire\SalesScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('lets a manager reverse a sale from the administration receipt', function () {
    $manager = app(CreateUserAction::class)->execute(
        'admin-reverse-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'admin-reverse-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'admin-reverse-register',
        'name' => 'Hauptkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 500);

    $this->actingAs($manager);

    Livewire::test(SalesScreen::class)
        ->call('showSale', $completed->id)
        ->assertSee('Verkauf vollständig stornieren')
        ->set('reversalReason', 'Kunde hat Ware zurückgegeben')
        ->call('reverseSelectedSale')
        ->assertHasNoErrors()
        ->assertSee('Vollständig storniert')
        ->assertSee('Kunde hat Ware zurückgegeben')
        ->assertDontSee('Verkauf vollständig stornieren');

    expect(SaleReversal::query()->where('sale_id', $completed->id)->count())->toBe(1)
        ->and($session->refresh()->cash_refunds_cents)->toBe(250);
});

it('validates the reversal reason in the administration workflow', function () {
    $manager = app(CreateUserAction::class)->execute(
        'admin-reverse-validation-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'admin-reverse-validation-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'admin-reverse-validation-register',
        'name' => 'Hauptkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 500);

    $this->actingAs($manager);

    Livewire::test(SalesScreen::class)
        ->call('showSale', $completed->id)
        ->set('reversalReason', 'x')
        ->call('reverseSelectedSale')
        ->assertHasErrors(['reversalReason' => 'min']);

    expect(SaleReversal::query()->count())->toBe(0);
});
