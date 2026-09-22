<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\EnsureDefaultRegisterAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\RecordCashWithdrawalAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('covers the complete v1 cash operation from roles to reporting and audit', function () {
    $createUser = app(CreateUserAction::class);

    $admin = $createUser->execute(
        username: 'accept-admin',
        pin: '811001',
        firstName: 'Acceptance',
        lastName: 'Admin',
        role: UserRole::Administrator,
    );

    $manager = $createUser->execute(
        username: 'accept-manager',
        pin: '811002',
        firstName: 'Acceptance',
        lastName: 'Manager',
        role: UserRole::Manager,
        actor: $admin,
    );

    $cashier = $createUser->execute(
        username: 'accept-cashier',
        pin: '811003',
        firstName: 'Acceptance',
        lastName: 'Cashier',
        role: UserRole::Cashier,
        actor: $admin,
    );

    $authorization = app(AuthorizationService::class);

    expect($authorization->allows($cashier, Permission::PosAccess))->toBeTrue()
        ->and($authorization->allows($cashier, Permission::AdministrationAccess))->toBeFalse()
        ->and($authorization->allows($manager, Permission::AdministrationAccess))->toBeTrue()
        ->and($authorization->allows($manager, Permission::CatalogManage))->toBeTrue()
        ->and($authorization->allows($manager, Permission::ReportsView))->toBeTrue()
        ->and($authorization->allows($manager, Permission::SettingsManage))->toBeFalse()
        ->and($authorization->allows($manager, Permission::AuditView))->toBeFalse()
        ->and($authorization->allows($admin, Permission::SettingsManage))->toBeTrue()
        ->and($authorization->allows($admin, Permission::AuditView))->toBeTrue()
        ->and($authorization->allows($admin, Permission::UsersRolesManage))->toBeTrue();

    $register = app(EnsureDefaultRegisterAction::class)->execute($admin);
    $category = app(CreateCategoryAction::class)->execute(
        name: 'Acceptance',
        sortOrder: 10,
        actor: $admin,
    );

    $paidProduct = app(CreateProductAction::class)->execute(
        category: $category,
        name: 'Acceptance Snack',
        shortName: 'Snack',
        priceCents: 250,
        sortOrder: 10,
        actor: $admin,
    );

    $freeProduct = app(CreateProductAction::class)->execute(
        category: $category,
        name: 'Acceptance Wasser',
        shortName: 'Wasser 0',
        priceCents: 0,
        sortOrder: 20,
        actor: $admin,
    );

    $session = app(OpenCashSessionAction::class)->execute(
        $register,
        $cashier,
        2000,
    );

    app(RecordCashDepositAction::class)->execute(
        $session,
        $cashier,
        1000,
        'Acceptance Wechselgeld',
    );

    $paidSale = app(StartSaleAction::class)->execute(
        $register,
        $session,
        $cashier,
    );
    app(AddProductToSaleAction::class)->execute($paidSale, $paidProduct);
    $paidSale = app(AddProductToSaleAction::class)->execute(
        $paidSale,
        $paidProduct,
    );
    $paidSale = app(CompleteCashSaleAction::class)->execute(
        $paidSale,
        1000,
    );

    expect($paidSale->status)->toBe(SaleStatus::Completed)
        ->and($paidSale->total_cents)->toBe(500)
        ->and($paidSale->items)->toHaveCount(1)
        ->and($paidSale->items->first()->quantity)->toBe(2)
        ->and($paidSale->payment)->not->toBeNull()
        ->and($paidSale->payment?->received_cents)->toBe(1000)
        ->and($paidSale->payment?->change_cents)->toBe(500);

    $freeSale = app(StartSaleAction::class)->execute(
        $register,
        $session,
        $cashier,
    );
    $freeSale = app(AddProductToSaleAction::class)->execute(
        $freeSale,
        $freeProduct,
    );
    $freeSale = app(CompleteCashSaleAction::class)->execute($freeSale, 0);

    expect($freeSale->status)->toBe(SaleStatus::Completed)
        ->and($freeSale->total_cents)->toBe(0)
        ->and($freeSale->payment)->toBeNull()
        ->and(Payment::query()->where('sale_id', $freeSale->id)->exists())->toBeFalse();

    app(RecordCashWithdrawalAction::class)->execute(
        $session,
        $cashier,
        500,
        'Acceptance Entnahme',
    );

    $session->refresh();

    expect($session->cash_sales_cents)->toBe(500)
        ->and($session->expectedCashCents())->toBe(3000);

    $closing = app(StartCashSessionClosingAction::class)->execute(
        $session,
        $cashier,
    );

    expect($closing->status)->toBe(CashSessionStatus::Closing);

    $closed = app(CloseCashSessionAction::class)->execute(
        $closing,
        $cashier,
        3000,
        'Acceptance Abschluss',
    );

    expect($closed->status)->toBe(CashSessionStatus::Closed)
        ->and($closed->closing_expected_cash_cents)->toBe(3000)
        ->and($closed->closing_counted_cash_cents)->toBe(3000)
        ->and($closed->closing_difference_cents)->toBe(0);

    $summary = app(GetDailySummaryQuery::class)->execute(
        now((string) config('kassensystem.timezone', 'Europe/Berlin'))
            ->format('Y-m-d'),
    );

    expect($summary['sales_count'])->toBe(2)
        ->and($summary['revenue_cents'])->toBe(500)
        ->and($summary['free_sales_count'])->toBe(1);

    $paidSummary = collect($summary['products'])
        ->firstWhere('product_id', $paidProduct->id);
    $freeSummary = collect($summary['products'])
        ->firstWhere('product_id', $freeProduct->id);

    expect($paidSummary)->toBe([
        'product_id' => $paidProduct->id,
        'product_name' => 'Acceptance Snack',
        'quantity' => 2,
        'revenue_cents' => 500,
    ])->and($freeSummary)->toBe([
        'product_id' => $freeProduct->id,
        'product_name' => 'Acceptance Wasser',
        'quantity' => 1,
        'revenue_cents' => 0,
    ]);

    $eventKeys = AuditEvent::query()
        ->pluck('event_key')
        ->unique()
        ->values()
        ->all();

    expect($eventKeys)
        ->toContain('user.created')
        ->toContain('register.created')
        ->toContain('category.created')
        ->toContain('product.created')
        ->toContain('cash_session.opened')
        ->toContain('cash_movement.deposit')
        ->toContain('sale.started')
        ->toContain('sale.completed')
        ->toContain('cash_movement.withdrawal')
        ->toContain('cash_session.closing_started')
        ->toContain('cash_session.closed');

    $auditPayload = json_encode(
        AuditEvent::query()
            ->get(['before', 'after', 'metadata'])
            ->toArray(),
        JSON_THROW_ON_ERROR,
    );

    expect($auditPayload)
        ->not->toContain('811001')
        ->not->toContain('811002')
        ->not->toContain('811003')
        ->not->toContain('pin_hash');
});

it('requires a documented comment for a closing difference', function () {
    $cashier = app(CreateUserAction::class)->execute(
        username: 'difference-cashier',
        pin: '822003',
        firstName: 'Difference',
        lastName: 'Cashier',
        role: UserRole::Cashier,
    );

    $register = app(EnsureDefaultRegisterAction::class)->execute($cashier);
    $session = app(OpenCashSessionAction::class)->execute(
        $register,
        $cashier,
        1000,
    );
    $closing = app(StartCashSessionClosingAction::class)->execute(
        $session,
        $cashier,
    );

    expect(
        fn () => app(CloseCashSessionAction::class)->execute(
            $closing,
            $cashier,
            900,
        ),
    )->toThrow(
        InvalidArgumentException::class,
        'A closing comment is required when cash differs from the expected amount.',
    );

    $closed = app(CloseCashSessionAction::class)->execute(
        $closing,
        $cashier,
        900,
        'Acceptance: 1,00 EUR Differenz geprüft',
    );

    expect($closed->status)->toBe(CashSessionStatus::Closed)
        ->and($closed->closing_expected_cash_cents)->toBe(1000)
        ->and($closed->closing_counted_cash_cents)->toBe(900)
        ->and($closed->closing_difference_cents)->toBe(-100)
        ->and($closed->closing_comment)->toBe(
            'Acceptance: 1,00 EUR Differenz geprüft',
        );
});
