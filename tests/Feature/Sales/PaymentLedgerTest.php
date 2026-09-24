<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompletePaypalSaleAction;
use App\Modules\Sales\Actions\CompleteSaleAction;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paymentLedgerFixture(string $suffix, int $priceCents = 500): array
{
    $cashier = app(CreateUserAction::class)->execute(
        "ledger-cashier-{$suffix}",
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => "ledger-register-{$suffix}",
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute("Ledger {$suffix}");
    $product = app(CreateProductAction::class)->execute($category, 'Artikel', 'Artikel', $priceCents);
    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);

    return [$cashier, $session, $sale];
}

it('validates a multi-entry payment ledger atomically and changes cash only by the cash part', function () {
    [$cashier, $session, $sale] = paymentLedgerFixture('mixed');

    $completed = app(CompleteSaleAction::class)->execute($sale, [
        [
            'method' => PaymentMethod::Cash,
            'amount_cents' => 200,
            'received_cents' => 200,
            'change_cents' => 0,
            'confirmed_by_user_id' => $cashier->id,
        ],
        [
            'method' => PaymentMethod::Paypal,
            'amount_cents' => 300,
            'received_cents' => 300,
            'change_cents' => 0,
            'confirmed_by_user_id' => $cashier->id,
            'provider_reference' => 'paypal.me/schoolcafe',
        ],
    ]);

    expect($completed->status)->toBe(SaleStatus::Completed)
        ->and($completed->payments)->toHaveCount(2)
        ->and($completed->payments->sum('amount_cents'))->toBe(500)
        ->and($session->refresh()->cash_sales_cents)->toBe(200)
        ->and($session->expectedCashCents())->toBe(1200)
        ->and(AuditEvent::query()->where('event_key', 'payment.paypal.confirmed')->count())->toBe(1);
});

it('rolls back the whole completion when payment sum and sale sum do not match', function () {
    [$cashier, $session, $sale] = paymentLedgerFixture('mismatch');

    expect(fn () => app(CompleteSaleAction::class)->execute($sale, [[
        'method' => PaymentMethod::Cash,
        'amount_cents' => 400,
        'received_cents' => 400,
        'change_cents' => 0,
        'confirmed_by_user_id' => $cashier->id,
    ]]))->toThrow(InvalidArgumentException::class, 'Payment total must exactly match the sale total.');

    expect($sale->refresh()->status)->toBe(SaleStatus::Open)
        ->and($sale->number)->toBeNull()
        ->and(Payment::query()->where('sale_id', $sale->id)->count())->toBe(0)
        ->and($session->refresh()->cash_sales_cents)->toBe(0);
});

it('confirms paypal manually exactly once without changing cash stock', function () {
    $admin = app(CreateUserAction::class)->execute(
        'ledger-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        paypalMeHandle: 'schoolcafe',
    );

    [$cashier, $session, $sale] = paymentLedgerFixture('paypal');

    $first = app(CompletePaypalSaleAction::class)->execute($sale, $cashier);
    $second = app(CompletePaypalSaleAction::class)->execute($sale, $cashier);

    $payment = Payment::query()->where('sale_id', $sale->id)->sole();

    expect($second->id)->toBe($first->id)
        ->and(Payment::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and($payment->method)->toBe(PaymentMethod::Paypal)
        ->and($payment->amount_cents)->toBe(500)
        ->and($payment->confirmed_by_user_id)->toBe($cashier->id)
        ->and($payment->provider_reference)->toBe('https://paypal.me/schoolcafe/5.00EUR')
        ->and($session->refresh()->cash_sales_cents)->toBe(0)
        ->and($session->expectedCashCents())->toBe(1000)
        ->and(AuditEvent::query()->where('event_key', 'payment.paypal.confirmed')->count())->toBe(1);
});

it('reverses only the cash part of a mixed ledger against the cash drawer', function () {
    [$cashier, $session, $sale] = paymentLedgerFixture('mixed-reversal');
    $sale->items()->update(['is_consumable' => false]);

    app(CompleteSaleAction::class)->execute($sale, [
        [
            'method' => PaymentMethod::Cash,
            'amount_cents' => 200,
            'received_cents' => 200,
            'change_cents' => 0,
            'confirmed_by_user_id' => $cashier->id,
        ],
        [
            'method' => PaymentMethod::Paypal,
            'amount_cents' => 300,
            'received_cents' => 300,
            'change_cents' => 0,
            'confirmed_by_user_id' => $cashier->id,
            'provider_reference' => 'https://paypal.me/schoolcafe/3.00EUR',
        ],
    ]);

    $manager = app(CreateUserAction::class)->execute(
        'ledger-reversal-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $reversal = app(ReverseCompletedSaleAction::class)->execute(
        $sale->refresh(),
        $manager,
        'Test reversal',
    );

    expect($reversal->amount_cents)->toBe(500)
        ->and($reversal->cash_refund_cents)->toBe(200)
        ->and($reversal->payment_method)->toBeNull()
        ->and($session->refresh()->cash_sales_cents)->toBe(200)
        ->and($session->cash_refunds_cents)->toBe(200)
        ->and($session->expectedCashCents())->toBe(1000);
});
