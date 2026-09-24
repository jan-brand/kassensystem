<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\PaypalMeLinkService;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use LogicException;

final class CompletePaypalSaleAction
{
    public function __construct(
        private readonly CompleteSaleAction $completeSale,
        private readonly GetSystemSettingsQuery $settings,
        private readonly PaypalMeLinkService $links,
    ) {}

    public function execute(Sale $sale, User $actor): Sale
    {
        $handle = $this->links->normalizeHandle($this->settings->execute()?->paypal_me_handle);

        if ($handle === null) {
            throw new LogicException('PayPal.me ist nicht konfiguriert.');
        }

        $total = (int) $sale->items()->sum('total_cents');

        if ($total <= 0) {
            throw new LogicException('Ein kostenloser Verkauf benötigt keine PayPal-Zahlung.');
        }

        $paymentUrl = $this->links->paymentUrl(
            $handle,
            $total,
            (string) config('kassensystem.currency', 'EUR'),
        );

        return $this->completeSale->execute($sale, [[
            'method' => PaymentMethod::Paypal,
            'amount_cents' => $total,
            'received_cents' => $total,
            'change_cents' => 0,
            'confirmed_by_user_id' => $actor->id,
            'provider_reference' => $paymentUrl,
        ]]);
    }
}
