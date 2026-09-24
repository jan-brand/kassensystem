<?php

namespace App\Modules\Sales\Queries;

use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;

final class GetCompletedSaleQuery
{
    public function execute(int $saleId): Sale
    {
        return Sale::query()
            ->with([
                'cashier',
                'register',
                'cashSession',
                'items',
                'payments.confirmedBy',
                'payment',
                'reversal.actor',
                'reversal.cashSession',
            ])
            ->where('status', SaleStatus::Completed->value)
            ->findOrFail($saleId);
    }
}
