<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('completes a cash sale with price snapshots and change', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'cashier',
        '123456',
        'Casey',
        'Cashier',
    );

    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);

    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 5000);

    $category = app(CreateCategoryAction::class)->execute('Backwaren');
    $product = app(CreateProductAction::class)->execute(
        $category,
        'Brezel',
        'Brezel',
        100,
    );

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);

    app(UpdateProductAction::class)->execute(
        $product,
        $category,
        'Brezel',
        'Brezel',
        120,
        0,
    );

    $completed = app(CompleteCashSaleAction::class)->execute($sale, 500);

    expect($completed->status)->toBe(SaleStatus::Completed)
        ->and($completed->number)->toMatch('/^\d{4}-\d{6}$/')
        ->and($completed->total_cents)->toBe(200)
        ->and($completed->items->first()->unit_price_cents)->toBe(100)
        ->and($completed->items->first()->quantity)->toBe(2)
        ->and($completed->payment->received_cents)->toBe(500)
        ->and($completed->payment->change_cents)->toBe(300)
        ->and($session->refresh()->cash_sales_cents)->toBe(200);
});

it('is idempotent when the same sale is completed twice', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'cashier',
        '123456',
        'Casey',
        'Cashier',
    );

    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);

    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);

    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Snack', 'Snack', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);

    $first = app(CompleteCashSaleAction::class)->execute($sale, 500);
    $second = app(CompleteCashSaleAction::class)->execute($sale, 500);

    expect($second->id)->toBe($first->id)
        ->and($second->number)->toBe($first->number)
        ->and(Payment::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and($session->refresh()->cash_sales_cents)->toBe(250);
});

it('completes a zero price sale without a payment', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'cashier',
        '123456',
        'Casey',
        'Cashier',
    );

    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);

    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);

    $category = app(CreateCategoryAction::class)->execute('Kostenlos');
    $product = app(CreateProductAction::class)->execute($category, 'Gratis', 'Gratis', 0);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);

    $completed = app(CompleteCashSaleAction::class)->execute($sale, 0);

    expect($completed->status)->toBe(SaleStatus::Completed)
        ->and($completed->total_cents)->toBe(0)
        ->and($completed->payment)->toBeNull()
        ->and($session->refresh()->cash_sales_cents)->toBe(0);
});

it('keeps completed sales and their items immutable', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'cashier',
        '123456',
        'Casey',
        'Cashier',
    );

    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);

    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Snack', 'Snack', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 500);
    $item = $completed->items->first();

    expect(fn () => $completed->update(['total_cents' => 999]))
        ->toThrow(\LogicException::class);

    expect(fn () => $item->update(['quantity' => 99]))
        ->toThrow(\LogicException::class);
});
