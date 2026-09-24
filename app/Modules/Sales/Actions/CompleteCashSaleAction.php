<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use InvalidArgumentException;

final class CompleteCashSaleAction
{
    public function __construct(private readonly CompleteSaleAction $completeSale) {}

    public function execute(Sale $sale, int $receivedCents): Sale
    {
        if ($receivedCents < 0) {
            throw new InvalidArgumentException('Received cash must not be negative.');
        }

        $total = (int) $sale->items()->sum('total_cents');

        return $this->completeSale->execute(
            $sale,
            $total > 0
                ? [[
                    'method' => PaymentMethod::Cash,
                    'amount_cents' => $total,
                    'received_cents' => $receivedCents,
                    'change_cents' => $receivedCents - $total,
                    'confirmed_by_user_id' => $sale->cashier_id,
                ]]
                : [],
        );
    }
}
