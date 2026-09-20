<?php

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashMovement;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('records deposits and withdrawals and protects the expected cash balance', function () {
    $cashier = app(CreateUserAction::class)->execute(
        username: 'cash-movement',
        pin: '123456',
        firstName: 'Mara',
        lastName: 'Money',
    );

    $this->actingAs($cashier);

    $component = Livewire::test(RegisterScreen::class)
        ->set('openingCash', '20,00')
        ->call('openCashSession')
        ->call('prepareCashMovement', 'deposit')
        ->set('cashMovementAmount', '5,00')
        ->set('cashMovementReason', 'Zusätzliches Wechselgeld')
        ->call('recordCashMovement')
        ->assertSet('screenError', null)
        ->call('prepareCashMovement', 'withdrawal')
        ->set('cashMovementAmount', '7,00')
        ->set('cashMovementReason', 'Bargeld sicher verwahrt')
        ->call('recordCashMovement')
        ->assertSet('screenError', null);

    $session = CashSession::query()->firstOrFail();

    expect($session->expectedCashCents())->toBe(1800)
        ->and(CashMovement::query()->count())->toBe(2)
        ->and(CashMovement::query()->where('type', CashMovementType::Deposit->value)->value('amount_cents'))->toBe(500)
        ->and(CashMovement::query()->where('type', CashMovementType::Withdrawal->value)->value('amount_cents'))->toBe(700);

    $component
        ->call('prepareCashMovement', 'withdrawal')
        ->set('cashMovementAmount', '18,01')
        ->set('cashMovementReason', 'Zu hohe Entnahme')
        ->call('recordCashMovement');

    expect($component->get('screenError'))->not->toBeNull()
        ->and(CashMovement::query()->count())->toBe(2)
        ->and($session->refresh()->expectedCashCents())->toBe(1800);
});

it('does not start closing while a cart is open and can cancel closing', function () {
    $cashier = app(CreateUserAction::class)->execute(
        username: 'closing-cart',
        pin: '123456',
        firstName: 'Clara',
        lastName: 'Closing',
    );

    $category = Category::query()->create([
        'name' => 'Kassenabschluss',
        'active' => true,
        'sort_order' => 10,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Testprodukt',
        'short_name' => 'Test',
        'price_cents' => 150,
        'active' => true,
        'sort_order' => 10,
    ]);

    $this->actingAs($cashier);

    $component = Livewire::test(RegisterScreen::class)
        ->set('openingCash', '10,00')
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('startCashClosing')
        ->assertSet(
            'screenError',
            'Vor dem Kassenabschluss muss der offene Warenkorb abgeschlossen oder verworfen werden.',
        );

    expect(CashSession::query()->firstOrFail()->status)->toBe(CashSessionStatus::Open);

    $component
        ->call('discardSale')
        ->call('startCashClosing')
        ->assertSet('screenError', null);

    expect(CashSession::query()->firstOrFail()->status)->toBe(CashSessionStatus::Closing);

    $component
        ->call('cancelCashClosing')
        ->assertSet('screenError', null);

    expect(CashSession::query()->firstOrFail()->status)->toBe(CashSessionStatus::Open);
});

it('closes the register with counted cash and requires a comment for differences', function () {
    $cashier = app(CreateUserAction::class)->execute(
        username: 'closing-count',
        pin: '123456',
        firstName: 'Karla',
        lastName: 'Count',
    );

    $this->actingAs($cashier);

    $component = Livewire::test(RegisterScreen::class)
        ->set('openingCash', '20,00')
        ->call('openCashSession')
        ->call('prepareCashMovement', 'deposit')
        ->set('cashMovementAmount', '5,00')
        ->set('cashMovementReason', 'Wechselgeld')
        ->call('recordCashMovement')
        ->call('startCashClosing')
        ->set('closingCountedCash', '24,50')
        ->call('closeCashSession');

    $session = CashSession::query()->firstOrFail();

    expect($component->get('screenError'))->not->toBeNull()
        ->and($session->refresh()->status)->toBe(CashSessionStatus::Closing);

    $component
        ->set('closingComment', '50 Cent fehlen beim Zählen')
        ->call('closeCashSession')
        ->assertSet('screenError', null)
        ->assertSet('notice', 'Kasse wurde mit dokumentierter Differenz abgeschlossen.');

    $session->refresh();

    expect($session->status)->toBe(CashSessionStatus::Closed)
        ->and($session->closing_expected_cash_cents)->toBe(2500)
        ->and($session->closing_counted_cash_cents)->toBe(2450)
        ->and($session->closing_difference_cents)->toBe(-50)
        ->and($session->closing_comment)->toBe('50 Cent fehlen beim Zählen')
        ->and($session->closed_by_user_id)->toBe($cashier->id);
});

it('blocks cash movements once closing has started', function () {
    $cashier = app(CreateUserAction::class)->execute(
        username: 'closing-lock',
        pin: '123456',
        firstName: 'Lina',
        lastName: 'Lock',
    );

    $this->actingAs($cashier);

    $component = Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('startCashClosing')
        ->set('cashMovementMode', 'deposit')
        ->set('cashMovementAmount', '1,00')
        ->set('cashMovementReason', 'Soll nicht funktionieren')
        ->call('recordCashMovement');

    expect($component->get('screenError'))->toBe('Die Kasse ist nicht geöffnet.')
        ->and(CashMovement::query()->count())->toBe(0)
        ->and(CashSession::query()->firstOrFail()->status)->toBe(CashSessionStatus::Closing);
});
