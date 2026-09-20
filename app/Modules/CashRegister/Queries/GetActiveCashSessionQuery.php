<?php

namespace App\Modules\CashRegister\Queries;

use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;

final class GetActiveCashSessionQuery
{
    public function execute(Register $register): ?CashSession
    {
        return CashSession::query()
            ->where('register_id', $register->id)
            ->whereIn('status', [
                CashSessionStatus::Open->value,
                CashSessionStatus::Closing->value,
            ])
            ->latest('id')
            ->first();
    }
}
