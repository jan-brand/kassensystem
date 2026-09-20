<?php

use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\RecordCashWithdrawalAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Reporting\Queries\GetCashSessionReportQuery;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Reporting\Services\DailyReportCsvExporter;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Surfaces\Administration\Livewire\ReportingScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('protects reporting on the server and allows administration roles', function () {
    $cashier = app(CreateUserAction::class)->execute('report-cashier', '123456', 'Casey', 'Cashier');
    $this->actingAs($cashier)->get(route('administration.reporting'))->assertForbidden();

    $manager = app(CreateUserAction::class)->execute(
        'report-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $this->actingAs($manager)->get(route('administration.reporting'))->assertOk();

    Livewire::test(ReportingScreen::class)
        ->assertStatus(200)
        ->assertSee('Berichte');
});

it('renders product totals and cash session closing data for the selected day', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $manager = app(CreateUserAction::class)->execute(
        'report-manager-data',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute('report-cashier-data', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create(['code' => 'register-report', 'name' => 'Hauptkasse', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    app(RecordCashDepositAction::class)->execute($session, $cashier, 200, 'Wechselgeld');
    app(RecordCashWithdrawalAction::class)->execute($session, $cashier, 100, 'Einkauf');

    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 150);
    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    app(CompleteCashSaleAction::class)->execute($sale, 500);

    $session = app(StartCashSessionClosingAction::class)->execute($session, $cashier);
    app(CloseCashSessionAction::class)->execute($session, $cashier, 1450, '50 Cent zu viel');

    $this->actingAs($manager);

    Livewire::test(ReportingScreen::class)
        ->set('date', '2026-09-20')
        ->call('applyDate')
        ->assertHasNoErrors()
        ->assertSee('Brezel')
        ->assertSee('2×')
        ->assertSee('3,00 €')
        ->assertSee('Hauptkasse')
        ->assertSee('2,00 €')
        ->assertSee('1,00 €')
        ->assertSee('14,00 €')
        ->assertSee('14,50 €')
        ->assertSee('0,50 €')
        ->assertSee('50 Cent zu viel');
});

it('validates report dates and can navigate between days', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $manager = app(CreateUserAction::class)->execute(
        'report-manager-date',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager);

    Livewire::test(ReportingScreen::class)
        ->assertSet('reportDate', '2026-09-20')
        ->set('date', 'not-a-date')
        ->call('applyDate')
        ->assertHasErrors(['date' => 'date_format'])
        ->assertSet('reportDate', '2026-09-20')
        ->call('previousDay')
        ->assertSet('reportDate', '2026-09-19')
        ->call('nextDay')
        ->assertSet('reportDate', '2026-09-20')
        ->call('nextDay')
        ->assertSet('reportDate', '2026-09-21')
        ->call('today')
        ->assertSet('reportDate', '2026-09-20');
});

it('exports the selected daily report as spreadsheet friendly csv', function () {
    Carbon::setTestNow('2026-09-20 12:00:00');

    $cashier = app(CreateUserAction::class)->execute('report-cashier-csv', '123456', 'Casey', 'Cashier');
    $register = Register::query()->create(['code' => 'register-csv', 'name' => 'Kasse CSV', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $session = app(StartCashSessionClosingAction::class)->execute($session, $cashier);
    app(CloseCashSessionAction::class)->execute($session, $cashier, 1000);

    $summary = app(GetDailySummaryQuery::class)->execute('2026-09-20');
    $sessions = app(GetCashSessionReportQuery::class)->execute('2026-09-20');
    $csv = app(DailyReportCsvExporter::class)->export($summary, $sessions);

    expect($csv)
        ->toStartWith("\xEF\xBB\xBFTyp;Berichtstag;")
        ->toContain('Übersicht;2026-09-20;;Tagesumsatz')
        ->toContain('Kassenschicht;2026-09-20;"Kasse CSV";#1')
        ->toContain(';10,00;0,00;0,00;0,00;10,00;10,00;0,00;');
});
