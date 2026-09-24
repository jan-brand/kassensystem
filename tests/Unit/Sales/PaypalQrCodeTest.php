<?php

use App\Modules\Sales\Services\PaypalMeLinkService;
use App\Modules\Sales\Services\QrCodeSvgService;

it('builds an amount-specific paypal me url and a deterministic qr symbol', function () {
    $links = new PaypalMeLinkService;
    $qr = new QrCodeSvgService;
    $url = $links->paymentUrl('example', 1234, 'EUR');
    $svg = $qr->render($url);

    expect($url)->toBe('https://paypal.me/example/12.34EUR')
        ->and($svg)->toContain('<svg')
        ->and($svg)->toContain('viewBox="0 0 49 49"')
        ->and(hash('sha256', $svg))->toBe('57717179e9d248a072946316a1aa9daf6215abd042defbb63573f46c77491a80');
});
