@php use App\Support\Money; @endphp
<main class="admin-page admin-page--dense space-y-6">
    <style>
        @media print {
            body * { visibility: hidden !important; }
            #sale-receipt, #sale-receipt * { visibility: visible !important; }
            #sale-receipt { position: absolute; left: 0; top: 0; width: 80mm; box-shadow: none !important; border: 0 !important; }
            .receipt-no-print { display: none !important; }
        }
    </style>

    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Administration</p>
                <h2 class="mt-2 text-3xl font-black">Verkäufe &amp; Belege</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Abgeschlossene Verkäufe nachvollziehen, Preis-Snapshots prüfen und vollständige Stornos als Gegenbuchung erfassen.
                </p>
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-300">
                {{ $canReverseSale ? 'Storno freigegeben' : 'Nur lesender Zugriff' }}
            </div>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
        <form wire:submit="applyFilters" class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black">Verkäufe filtern</h3>
                    <p class="text-sm text-slate-500">Nach Verkaufsnummer und Abschlussdatum suchen.</p>
                </div>
                <button type="button" wire:click="resetFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50">
                    Zurücksetzen
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <label class="space-y-1 md:col-span-2">
                    <span class="text-sm font-bold text-slate-700">Verkaufsnummer</span>
                    <input wire:model="number" type="text" placeholder="z. B. 2026-000001" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('number') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Von</span>
                    <input wire:model="dateFrom" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('dateFrom') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Bis</span>
                    <input wire:model="dateTo" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('dateTo') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-black text-white hover:bg-slate-800">
                    Anwenden
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="text-lg font-black">Abgeschlossene Verkäufe</h3>
            <p class="text-sm text-slate-500">Neueste zuerst · {{ $sales->total() }} Treffer</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Verkaufsnummer</th>
                        <th class="px-5 py-3">Zeitpunkt</th>
                        <th class="px-5 py-3">Kassierer</th>
                        <th class="px-5 py-3">Kasse</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Gesamt</th>
                        <th class="px-5 py-3 text-right sm:px-6">Beleg</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sales as $sale)
                        <tr wire:key="sale-{{ $sale->id }}">
                            <td class="whitespace-nowrap px-5 py-4 font-mono text-sm font-black text-slate-900 sm:px-6">{{ $sale->number }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $sale->completed_at?->format('d.m.Y H:i:s') }}</td>
                            <td class="px-5 py-4 text-slate-700">
                                <div class="font-bold">{{ $sale->cashier->auditDisplayName() }}</div>
                                <div class="text-xs text-slate-500">{{ $sale->cashier->username }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-700">{{ $sale->register->name }}</td>
                            <td class="px-5 py-4">
                                @if($sale->reversal)
                                    <span class="rounded-lg bg-red-100 px-2 py-1 text-xs font-black text-red-800">Storniert</span>
                                @else
                                    <span class="rounded-lg bg-emerald-100 px-2 py-1 text-xs font-black text-emerald-800">Abgeschlossen</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-black">{{ Money::format((int) $sale->total_cents, $currency) }}</td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <button type="button" wire:click="showSale({{ $sale->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-black hover:bg-slate-50">
                                    Öffnen
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500">Keine abgeschlossenen Verkäufe für diese Filter gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                {{ $sales->links() }}
            </div>
        @endif
    </section>

    @if($selectedSale)
        <div class="admin-overlay fixed inset-0 z-50 overflow-y-auto p-4 sm:p-8" wire:click.self="closeSale">
            <div class="mx-auto flex max-w-5xl justify-center gap-6">
                <section id="sale-receipt" class="admin-receipt w-full max-w-md overflow-hidden">
                    <header class="border-b border-dashed px-6 py-6 text-center">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500">Beleg</p>
                        <h3 class="mt-2 text-2xl font-black">{{ $cafeteriaName }}</h3>
                        <p class="mt-1 font-mono text-sm font-bold">{{ $selectedSale->number }}</p>
                        @if($selectedSale->reversal)
                            <span class="mt-3 inline-flex rounded-lg bg-red-100 px-2.5 py-1 text-xs font-black text-red-800">Vollständig storniert</span>
                        @endif
                    </header>

                    <div class="space-y-5 px-6 py-5 text-sm">
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-slate-600">
                            <dt>Datum</dt><dd class="text-right font-bold text-slate-900">{{ $selectedSale->completed_at?->format('d.m.Y H:i:s') }}</dd>
                            <dt>Kasse</dt><dd class="text-right font-bold text-slate-900">{{ $selectedSale->register->name }}</dd>
                            <dt>Kassierer</dt><dd class="text-right font-bold text-slate-900">{{ $selectedSale->cashier->auditDisplayName() }}</dd>
                        </dl>

                        <div class="border-y border-dashed border-slate-300 py-3">
                            <div class="grid grid-cols-[1fr_auto] gap-3 text-xs font-bold uppercase tracking-wide text-slate-500">
                                <span>Position</span><span>Summe</span>
                            </div>
                            <div class="mt-2 divide-y divide-slate-100">
                                @foreach($selectedSale->items as $item)
                                    <div class="grid grid-cols-[1fr_auto] gap-3 py-3">
                                        <div>
                                            <div class="font-black">{{ $item->product_name }}</div>
                                            <div class="text-xs text-slate-500">{{ $item->quantity }} × {{ Money::format((int) $item->unit_price_cents, $currency) }}</div>
                                        </div>
                                        <div class="whitespace-nowrap font-black">{{ Money::format((int) $item->total_cents, $currency) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-lg font-black">
                            <span>Gesamt</span>
                            <span>{{ Money::format((int) $selectedSale->total_cents, $currency) }}</span>
                        </div>

                        @if($selectedSale->payment)
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 border-t border-dashed border-slate-300 pt-4 text-slate-600">
                                <dt>Zahlungsart</dt><dd class="text-right font-bold text-slate-900">Bar</dd>
                                <dt>Gegeben</dt><dd class="text-right font-bold text-slate-900">{{ Money::format((int) $selectedSale->payment->received_cents, $currency) }}</dd>
                                <dt>Rückgeld</dt><dd class="text-right font-bold text-slate-900">{{ Money::format((int) $selectedSale->payment->change_cents, $currency) }}</dd>
                            </dl>
                        @else
                            <div class="rounded-2xl bg-slate-100 px-4 py-3 text-center font-bold text-slate-700">
                                Kostenloser Verkauf · keine Zahlung
                            </div>
                        @endif

                        @if($selectedSale->reversal)
                            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-950">
                                <p class="font-black">Vollständig storniert</p>
                                <dl class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 text-xs">
                                    <dt class="text-red-700">Zeitpunkt</dt>
                                    <dd class="text-right font-bold">{{ $selectedSale->reversal->reversed_at?->format('d.m.Y H:i:s') }}</dd>
                                    <dt class="text-red-700">Durch</dt>
                                    <dd class="text-right font-bold">{{ $selectedSale->reversal->actor->auditDisplayName() }}</dd>
                                    <dt class="text-red-700">Grund</dt>
                                    <dd class="text-right font-bold">{{ $selectedSale->reversal->reason }}</dd>
                                    @if($selectedSale->reversal->cash_refund_cents > 0)
                                        <dt class="text-red-700">Barauszahlung</dt>
                                        <dd class="text-right font-bold">{{ Money::format((int) $selectedSale->reversal->cash_refund_cents, $currency) }}</dd>
                                        <dt class="text-red-700">Kassenschicht</dt>
                                        <dd class="text-right font-bold">#{{ $selectedSale->reversal->cashSession?->id }}</dd>
                                    @endif
                                </dl>
                            </div>
                        @elseif($canReverseSale)
                            <div class="receipt-no-print rounded-2xl border border-red-200 bg-red-50 p-4">
                                <h4 class="font-black text-red-950">Verkauf vollständig stornieren</h4>
                                <p class="mt-1 text-xs leading-5 text-red-800">
                                    Der Originalverkauf bleibt unverändert. Bei Barzahlung wird die Auszahlung der aktuell offenen Kassenschicht derselben Kasse belastet.
                                </p>

                                <label class="mt-4 block">
                                    <span class="text-xs font-black uppercase tracking-wide text-red-900">Stornogrund</span>
                                    <textarea wire:model="reversalReason" rows="3" maxlength="500" class="mt-1 w-full rounded-xl border border-red-300 bg-white px-3 py-2 text-sm text-slate-950" placeholder="Grund für das vollständige Storno"></textarea>
                                </label>
                                @error('reversalReason')
                                    <p class="mt-2 text-xs font-bold text-red-700">{{ $message }}</p>
                                @enderror

                                <button
                                    type="button"
                                    wire:click="reverseSelectedSale"
                                    wire:confirm="Diesen Verkauf wirklich vollständig stornieren?"
                                    wire:loading.attr="disabled"
                                    class="mt-3 w-full rounded-xl bg-red-700 px-4 py-3 text-sm font-black text-white hover:bg-red-800 disabled:opacity-50"
                                >
                                    Vollständig stornieren
                                </button>
                            </div>
                        @endif

                        <p class="text-center text-xs leading-5 text-slate-500">
                            Gespeicherter Verkaufsbeleg. Produktname und Einzelpreis stammen aus dem unveränderlichen Verkaufssnapshot.
                        </p>
                    </div>

                    <footer class="admin-dialog__footer receipt-no-print flex gap-2 border-t p-4">
                        <button type="button" onclick="window.print()" class="admin-dialog__button-primary flex-1 px-4 py-2.5 text-sm">Drucken</button>
                        <button type="button" wire:click="closeSale" class="admin-dialog__button-secondary flex-1 px-4 py-2.5 text-sm">Schließen</button>
                    </footer>
                </section>
            </div>
        </div>
    @endif
</main>
