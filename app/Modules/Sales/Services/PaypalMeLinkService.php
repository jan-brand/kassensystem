<?php

namespace App\Modules\Sales\Services;

use InvalidArgumentException;

final class PaypalMeLinkService
{
    public function normalizeHandle(?string $handle): ?string
    {
        $handle = trim((string) $handle);

        if ($handle === '') {
            return null;
        }

        if (! preg_match('/^[A-Za-z0-9]{1,20}$/D', $handle)) {
            throw new InvalidArgumentException('Der PayPal.me-Name darf nur Buchstaben und Zahlen enthalten und höchstens 20 Zeichen lang sein.');
        }

        return $handle;
    }

    public function paymentUrl(string $handle, int $amountCents, string $currency = 'EUR'): string
    {
        $handle = $this->normalizeHandle($handle);

        if ($handle === null) {
            throw new InvalidArgumentException('Es ist kein PayPal.me-Konto konfiguriert.');
        }

        if ($amountCents <= 0) {
            throw new InvalidArgumentException('PayPal.me benötigt einen positiven Zahlungsbetrag.');
        }

        $currency = strtoupper(trim($currency));

        if (! preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new InvalidArgumentException('Ungültiger Währungscode.');
        }

        $amount = number_format($amountCents / 100, 2, '.', '');

        return "https://paypal.me/{$handle}/{$amount}{$currency}";
    }
}
