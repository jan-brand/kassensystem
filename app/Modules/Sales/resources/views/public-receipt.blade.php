@php
    use App\Modules\Sales\Enums\DigitalReceiptKind;
    use App\Modules\Sales\Enums\PaymentMethod;
    use App\Support\Money;

    $sale = $receipt->sale;
    $reversal = $receipt->reversal;
    $isReversal = $receipt->kind === DigitalReceiptKind::Reversal;
@endphp
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $isReversal ? 'Stornobeleg' : 'Beleg' }} · {{ $receipt->sale_number_snapshot }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-100 px-4 py-8 text-slate-950">
    <main class="mx-auto w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <header class="border-b border-dashed border-slate-300 px-6 py-6 text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">
                {{ $isReversal ? 'Stornobeleg' : 'Digitaler Beleg' }}
            </p>
            <h1 class="mt-2 text-2xl font-black">{{ $receipt->merchant_name_snapshot }}</h1>
            <p class="mt-1 font-mono text-sm font-bold">{{ $receipt->sale_number_snapshot }}</p>
            @if($isReversal)
                <span class="mt-3 inline-flex rounded-lg bg-red-100 px-3 py-1 text-xs font-black text-red-800">Gegenbuchung</span>
            @elseif($sale->reversal)
                <span class="mt-3 inline-flex rounded-lg bg-red-100 px-3 py-1 text-xs font-black text-red-800">Später vollständig storniert</span>
            @endif
        </header>

        <div class="space-y-5 px-6 py-5 text-sm">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-slate-600">
                <dt>{{ $isReversal ? 'Stornozeitpunkt' : 'Verkaufszeitpunkt' }}</dt>
                <dd class="text-right font-bold text-slate-900">
                    {{ ($isReversal ? $reversal?->reversed_at : $sale->completed_at)?->format('d.m.Y H:i:s') }}
                </dd>
            </dl>

            <div class="border-y border-dashed border-slate-300 py-3">
                <div class="grid grid-cols-[1fr_auto] gap-3 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <span>Position</span><span>{{ $isReversal ? 'Gegenbuchung' : 'Summe' }}</span>
                </div>
                <div class="mt-2 divide-y divide-slate-100">
                    @foreach($sale->items as $item)
                        <div class="grid grid-cols-[1fr_auto] gap-3 py-3">
                            <div>
                                <div class="font-black">{{ $item->product_name }}</div>
                                @if($item->discount_type)
                                    <div class="text-xs text-slate-500">
                                        {{ $item->quantity }} ×
                                        <span class="line-through">{{ Money::format((int) ($item->original_unit_price_cents ?? $item->unit_price_cents), $receipt->currency_snapshot) }}</span>
                                        {{ Money::format((int) $item->unit_price_cents, $receipt->currency_snapshot) }}
                                    </div>
                                    @if($item->discount_label)
                                        <div class="mt-1 text-xs font-bold text-emerald-700">{{ $item->discount_label }}</div>
                                    @endif
                                @else
                                    <div class="text-xs text-slate-500">{{ $item->quantity }} × {{ Money::format((int) $item->unit_price_cents, $receipt->currency_snapshot) }}</div>
                                @endif
                            </div>
                            <div class="whitespace-nowrap font-black">
                                {{ $isReversal ? '−' : '' }}{{ Money::format((int) $item->total_cents, $receipt->currency_snapshot) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between text-lg font-black">
                <span>{{ $isReversal ? 'Storno gesamt' : 'Gesamt' }}</span>
                <span>{{ $isReversal ? '−' : '' }}{{ Money::format((int) $sale->total_cents, $receipt->currency_snapshot) }}</span>
            </div>

            @if($isReversal)
                @if(($reversal?->cash_refund_cents ?? 0) > 0)
                    <div class="rounded-2xl bg-slate-100 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-slate-600">Barauszahlung</span>
                            <span class="font-black">{{ Money::format((int) $reversal->cash_refund_cents, $receipt->currency_snapshot) }}</span>
                        </div>
                    </div>
                @endif
                <p class="rounded-2xl bg-red-50 p-4 text-xs leading-5 text-red-900">
                    Dieser Stornobeleg dokumentiert die Gegenbuchung im Kassensystem. Interne Stornogründe und Mitarbeiterdaten werden hier nicht veröffentlicht.
                </p>
            @elseif($sale->payments->isNotEmpty())
                <div class="space-y-3 border-t border-dashed border-slate-300 pt-4">
                    @foreach($sale->payments as $payment)
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 rounded-2xl bg-slate-50 p-3 text-slate-600">
                            <dt>Zahlungsart</dt><dd class="text-right font-bold text-slate-900">{{ $payment->method->label() }}</dd>
                            <dt>Betrag</dt><dd class="text-right font-bold text-slate-900">{{ Money::format((int) $payment->amount_cents, $receipt->currency_snapshot) }}</dd>
                            @if($payment->method === PaymentMethod::Cash)
                                <dt>Gegeben</dt><dd class="text-right font-bold text-slate-900">{{ Money::format((int) $payment->received_cents, $receipt->currency_snapshot) }}</dd>
                                <dt>Rückgeld</dt><dd class="text-right font-bold text-slate-900">{{ Money::format((int) $payment->change_cents, $receipt->currency_snapshot) }}</dd>
                            @endif
                        </dl>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center font-bold text-slate-700">
                    Kostenloser Verkauf · keine Zahlung
                </div>
            @endif

            <p class="text-center text-xs leading-5 text-slate-500">
                Öffentlicher Abruf bis {{ $receipt->expires_at->format('d.m.Y H:i') }}.
                Positionsnamen, Preise und Rabatte stammen aus dem unveränderlichen Verkaufssnapshot.
            </p>
        </div>
    </main>
</body>
</html>
