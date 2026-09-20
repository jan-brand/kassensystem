<?php

namespace App\Modules\Sales\Queries;

use App\Modules\CashRegister\Models\Register;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;

final class GetOpenSaleForRegisterQuery
{
    public function execute(Register $register): ?Sale
    {
        return Sale::query()
            ->with('items')
            ->where('register_id', $register->id)
            ->where('status', SaleStatus::Open->value)
            ->latest('id')
            ->first();
    }
}
