<?php

namespace App\Modules\Sales\Actions;

use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Queries\GetOpenSaleForRegisterQuery;
use Illuminate\Support\Facades\DB;

final class AddProductToCartAction
{
    public function __construct(
        private readonly GetOpenSaleForRegisterQuery $openSaleQuery,
        private readonly StartSaleAction $startSale,
        private readonly AddProductToSaleAction $addProduct,
    ) {}

    public function execute(
        Register $register,
        CashSession $cashSession,
        User $cashier,
        Product $product,
    ): Sale {
        return DB::transaction(function () use ($register, $cashSession, $cashier, $product): Sale {
            $sale = $this->openSaleQuery->execute($register)
                ?? $this->startSale->execute($register, $cashSession, $cashier);

            return $this->addProduct->execute($sale, $product);
        });
    }
}
