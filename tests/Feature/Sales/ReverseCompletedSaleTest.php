<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\SaleReversal;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates one immutable counterbooking and reduces expected cash exactly once', function () {
    $manager = app(CreateUserAction::class)->execute(
        'reverse-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'reverse-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'reverse-register',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 500);

    expect($session->refresh()->expectedCashCents())->toBe(1250);

    $first = app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Kunde hat den Kauf direkt zurückgegeben',
    );
    $second = app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Kunde hat den Kauf direkt zurückgegeben',
    );

    $completed->refresh()->load('items');

    expect($second->id)->toBe($first->id)
        ->and(SaleReversal::query()->where('sale_id', $completed->id)->count())->toBe(1)
        ->and($first->payment_method)->toBe(PaymentMethod::Cash)
        ->and($first->amount_cents)->toBe(250)
        ->and($first->cash_refund_cents)->toBe(250)
        ->and($first->cash_session_id)->toBe($session->id)
        ->and($first->reason)->toBe('Kunde hat den Kauf direkt zurückgegeben')
        ->and($session->refresh()->cash_refunds_cents)->toBe(250)
        ->and($session->expectedCashCents())->toBe(1000)
        ->and($completed->status)->toBe(SaleStatus::Completed)
        ->and($completed->total_cents)->toBe(250)
        ->and($completed->items->first()->product_name)->toBe('Brezel')
        ->and(AuditEvent::query()->where('event_key', 'sale.reversed')->count())->toBe(1);

    $audit = AuditEvent::query()->where('event_key', 'sale.reversed')->sole();

    expect($audit->actor_user_id)->toBe($manager->id)
        ->and($audit->subject_id)->toBe($completed->id)
        ->and($audit->after['sale_reversal_id'] ?? null)->toBe($first->id)
        ->and($audit->after['cash_refund_cents'] ?? null)->toBe(250);

    expect(fn () => $first->update(['reason' => 'Geändert']))
        ->toThrow(LogicException::class);

    expect(fn () => $first->delete())
        ->toThrow(LogicException::class);
});

it('allows a zero euro reversal without inventing a payment or open cash session', function () {
    $manager = app(CreateUserAction::class)->execute(
        'reverse-free-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'reverse-free-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'reverse-free-register',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Kostenlos');
    $product = app(CreateProductAction::class)->execute($category, 'Wasser gratis', 'Wasser', 0);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 0);

    $closing = app(StartCashSessionClosingAction::class)->execute($session, $cashier);
    app(CloseCashSessionAction::class)->execute($closing, $cashier, 0);

    $reversal = app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Kostenlose Ausgabe zurückgenommen',
    );

    expect($completed->refresh()->payment)->toBeNull()
        ->and($reversal->payment_method)->toBeNull()
        ->and($reversal->amount_cents)->toBe(0)
        ->and($reversal->cash_refund_cents)->toBe(0)
        ->and($reversal->cash_session_id)->toBeNull();
});

it('requires an open drawer with enough expected cash for a cash reversal', function () {
    $manager = app(CreateUserAction::class)->execute(
        'reverse-later-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'reverse-later-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'reverse-later-register',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Snack', 'Snack', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 250);

    $closing = app(StartCashSessionClosingAction::class)->execute($session, $cashier);
    app(CloseCashSessionAction::class)->execute($closing, $cashier, 250);

    expect(fn () => app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Rückgabe nach Kassenabschluss',
    ))->toThrow(LogicException::class);

    $refundSession = app(OpenCashSessionAction::class)->execute($register, $cashier, 100);

    expect(fn () => app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Rückgabe nach Kassenabschluss',
    ))->toThrow(LogicException::class);

    app(RecordCashDepositAction::class)->execute(
        $refundSession,
        $cashier,
        200,
        'Zusätzliches Bargeld für Rückzahlung',
    );

    $reversal = app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $manager,
        'Rückgabe nach Kassenabschluss',
    );

    expect($reversal->cash_session_id)->toBe($refundSession->id)
        ->and($refundSession->refresh()->cash_refunds_cents)->toBe(250)
        ->and($refundSession->expectedCashCents())->toBe(50);
});

it('does not allow cashiers to reverse completed sales', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'reverse-denied-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'reverse-denied-register',
        'name' => 'Kasse 1',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Snacks');
    $product = app(CreateProductAction::class)->execute($category, 'Snack', 'Snack', 250);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $completed = app(CompleteCashSaleAction::class)->execute($sale, 250);

    expect(fn () => app(ReverseCompletedSaleAction::class)->execute(
        $completed,
        $cashier,
        'Nicht erlaubt',
    ))->toThrow(AuthorizationException::class);

    expect(SaleReversal::query()->count())->toBe(0)
        ->and($session->refresh()->cash_refunds_cents)->toBe(0);
});
