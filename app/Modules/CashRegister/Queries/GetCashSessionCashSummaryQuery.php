<?php

namespace App\Modules\CashRegister\Queries;

use App\Modules\CashRegister\Enums\CashMovementType;
use App\Modules\CashRegister\Models\CashSession;

final class GetCashSessionCashSummaryQuery
{
    /**
     * @return array{
     *     opening_cash_cents: int,
     *     cash_sales_cents: int,
     *     cash_refunds_cents: int,
     *     deposits_cents: int,
     *     withdrawals_cents: int,
     *     expected_cash_cents: int
     * }
     */
    public function execute(CashSession $session): array
    {
        $deposits = (int) $session->movements()
            ->where('type', CashMovementType::Deposit->value)
            ->sum('amount_cents');

        $withdrawals = (int) $session->movements()
            ->where('type', CashMovementType::Withdrawal->value)
            ->sum('amount_cents');

        return [
            'opening_cash_cents' => (int) $session->opening_cash_cents,
            'cash_sales_cents' => (int) $session->cash_sales_cents,
            'cash_refunds_cents' => (int) $session->cash_refunds_cents,
            'deposits_cents' => $deposits,
            'withdrawals_cents' => $withdrawals,
            'expected_cash_cents' => (int) $session->opening_cash_cents
                + (int) $session->cash_sales_cents
                - (int) $session->cash_refunds_cents
                + $deposits
                - $withdrawals,
        ];
    }
}
