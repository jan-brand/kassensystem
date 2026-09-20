<?php

namespace App\Surfaces\Pos\Actions;

use App\Modules\CashRegister\Actions\StartCashSessionClosingAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;
use LogicException;

final class StartRegisterClosingAction
{
    public function __construct(
        private readonly StartCashSessionClosingAction $startClosing,
    ) {}

    public function execute(Register $register, User $user): CashSession
    {
        return DB::transaction(function () use ($register, $user): CashSession {
            Register::query()->lockForUpdate()->findOrFail($register->id);

            $session = CashSession::query()
                ->where('register_id', $register->id)
                ->whereIn('status', [
                    CashSessionStatus::Open->value,
                    CashSessionStatus::Closing->value,
                ])
                ->latest('id')
                ->first();

            if ($session === null || $session->status !== CashSessionStatus::Open) {
                throw new LogicException('Die Kasse ist nicht geöffnet.');
            }

            $hasOpenSale = Sale::query()
                ->where('register_id', $register->id)
                ->where('status', SaleStatus::Open->value)
                ->exists();

            if ($hasOpenSale) {
                throw new LogicException(
                    'Vor dem Kassenabschluss muss der offene Warenkorb abgeschlossen oder verworfen werden.',
                );
            }

            return $this->startClosing->execute($session, $user);
        });
    }
}
