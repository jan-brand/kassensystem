<?php

use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Reporting\Queries\GetCashSessionReportQuery;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Reporting\Services\CsvExporter;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('builds a daily revenue and product summary using the configured local day', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $cashier = app(CreateUserAction::class)->execute('cashier', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $paid = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 100);
    $free = app(CreateProductAction::class)->execute($category, 'Wasser gratis', 'Wasser', 0);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $paid);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $paid);
    app(CompleteCashSaleAction::class)->execute($sale, 500);

    $freeSale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $freeSale = app(AddProductToSaleAction::class)->execute($freeSale, $free);
    app(CompleteCashSaleAction::class)->execute($freeSale, 0);

    $summary = app(GetDailySummaryQuery::class)->execute('2026-09-20');

    expect($summary['sales_count'])->toBe(2)
        ->and($summary['revenue_cents'])->toBe(200)
        ->and($summary['free_sales_count'])->toBe(1)
        ->and($summary['products'])->toHaveCount(2)
        ->and($summary['products'][0]['product_name'])->toBe('Brezel')
        ->and($summary['products'][0]['quantity'])->toBe(2)
        ->and($summary['products'][0]['revenue_cents'])->toBe(200);
});

it('returns cash sessions by their actual closing day', function () {
    Carbon::setTestNow('2026-09-20 23:50:00');

    $cashier = app(CreateUserAction::class)->execute('cashier', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create([
        'code' => 'register-1',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);

    Carbon::setTestNow('2026-09-21 00:10:00');
    $session = app(StartCashSessionClosingAction::class)->execute($session, $cashier);
    app(CloseCashSessionAction::class)->execute($session, $cashier, 1000);

    expect(app(GetCashSessionReportQuery::class)->execute('2026-09-20'))->toHaveCount(0)
        ->and(app(GetCashSessionReportQuery::class)->execute('2026-09-21'))->toHaveCount(1);
});

it('exports semicolon separated utf8 csv data', function () {
    $csv = app(CsvExporter::class)->export(
        ['Produkt', 'Menge', 'Umsatz Cent'],
        [
            ['Brezel', 2, 200],
            ['Getränk; groß', 1, 150],
        ],
    );

    expect($csv)->toStartWith("\xEF\xBB\xBFProdukt;Menge;")
        ->and($csv)->toContain('Brezel;2;200')
        ->and($csv)->toContain('"Getränk; groß";1;150');
});
