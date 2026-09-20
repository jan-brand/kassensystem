<?php

use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\RecordCashWithdrawalAction;
use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Actions\CreateUserAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('calculates cash closing with movements and requires a comment for differences', function () {
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

    app(RecordCashDepositAction::class)->execute($session, $cashier, 1000, 'Zusätzliches Wechselgeld');
    app(RecordCashWithdrawalAction::class)->execute($session, $cashier, 500, 'Geld sicher verwahrt');

    $session->update(['cash_sales_cents' => 2000]);

    expect($session->refresh()->expectedCashCents())->toBe(7500);

    $session = app(StartCashSessionClosingAction::class)->execute($session, $cashier);

    expect(fn () => app(CloseCashSessionAction::class)->execute($session, $cashier, 7400))
        ->toThrow(InvalidArgumentException::class);

    $closed = app(CloseCashSessionAction::class)->execute(
        $session,
        $cashier,
        7400,
        'Ein Euro fehlt',
    );

    expect($closed->status)->toBe(CashSessionStatus::Closed)
        ->and($closed->closing_expected_cash_cents)->toBe(7500)
        ->and($closed->closing_difference_cents)->toBe(-100);

    expect(fn () => $closed->update(['closing_comment' => 'Nachträglich geändert']))
        ->toThrow(LogicException::class);

    expect(fn () => $closed->delete())
        ->toThrow(LogicException::class);
});
