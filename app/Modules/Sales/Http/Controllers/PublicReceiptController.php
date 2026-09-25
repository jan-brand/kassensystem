<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Modules\Sales\Models\DigitalReceipt;
use App\Modules\Sales\Services\DigitalReceiptService;
use Illuminate\Http\Response;

final class PublicReceiptController
{
    public function __invoke(string $token, DigitalReceiptService $receipts): Response
    {
        $receipt = $receipts->resolvePublic($token);

        if (! $receipt instanceof DigitalReceipt) {
            abort(404);
        }

        return response()
            ->view('sales::public-receipt', [
                'receipt' => $receipt,
            ])
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
