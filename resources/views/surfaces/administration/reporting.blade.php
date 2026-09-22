@php
    use App\Modules\CashRegister\Enums\CashMovementType;
    use App\Support\Money;
@endphp

<main class="admin-page admin-page--dense space-y-6">
    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Administration</p>
                <h2 class="mt-2 text-3xl font-black">Berichte</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Verkäufe werden nach dem lokalen Verkaufstag in {{ $timezone }} ausgewertet. Kassenschichten zählen zum Tag ihres tatsächlichen Abschlusses.
                </p>
            </div>

            <button wire:click="downloadCsv" type="button" class="rounded-xl bg-white px-4 py-2.5 text-sm font-black text-slate-950 hover:bg-slate-100">
                CSV herunterladen
            </button>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h3 class="text-lg font-black">Berichtstag</h3>
                <p class="text-sm text-slate-500">Aktuell angezeigt: {{ \Carbon\CarbonImmutable::createFromFormat('!Y-m-d', $reportDate, $timezone)?->format('d.m.Y') }}</p>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <button wire:click="previousDay" type="button" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-bold hover:bg-slate-50">Vortag</button>

                <label class="space-y-1">
                    <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">Datum</span>
                    <input wire:model="date" type="date" class="rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('date') <span class="block text-xs font-bold text-red-600">{{ $message }}</span> @enderror
                </label>

                <button wire:click="applyDate" type="button" class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800">Anzeigen</button>
                <button wire:click="today" type="button" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-bold hover:bg-slate-50">Heute</button>
                <button wire:click="nextDay" type="button" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-bold hover:bg-slate-50">Folgetag</button>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Tagesumsatz</p>
            <p class="mt-2 text-3xl font-black">{{ Money::format($summary['revenue_cents'], $currency) }}</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Verkäufe</p>
            <p class="mt-2 text-3xl font-black">{{ $summary['sales_count'] }}</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Kostenlose Verkäufe</p>
            <p class="mt-2 text-3xl font-black">{{ $summary['free_sales_count'] }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="text-lg font-black">Produktumsätze</h3>
            <p class="text-sm text-slate-500">Verkaufte Menge und Umsatz auf Basis der gespeicherten Verkaufssnapshots.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Produkt</th>
                        <th class="px-5 py-3 text-right">Menge</th>
                        <th class="px-5 py-3 text-right sm:px-6">Umsatz</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary['products'] as $product)
                        <tr>
                            <td class="px-5 py-3 font-bold sm:px-6">{{ $product['product_name'] }}</td>
                            <td class="px-5 py-3 text-right">{{ $product['quantity'] }}×</td>
                            <td class="px-5 py-3 text-right font-black sm:px-6">{{ Money::format($product['revenue_cents'], $currency) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">Keine Verkäufe an diesem Tag.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-4">
        <div>
            <h3 class="text-xl font-black">Abgeschlossene Kassenschichten</h3>
            <p class="text-sm text-slate-500">Schichten werden nach ihrem tatsächlichen Abschlusszeitpunkt dem Berichtstag zugeordnet.</p>
        </div>

        @forelse($sessions as $session)
            @php
                $deposits = (int) $session->movements->filter(fn ($movement) => $movement->type === CashMovementType::Deposit)->sum('amount_cents');
                $withdrawals = (int) $session->movements->filter(fn ($movement) => $movement->type === CashMovementType::Withdrawal)->sum('amount_cents');
                $difference = (int) $session->closing_difference_cents;
            @endphp

            <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-lg font-black">{{ $session->register->name }}</h4>
                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">Schicht #{{ $session->id }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $session->opened_at?->format('d.m.Y H:i') }} bis {{ $session->closed_at?->format('d.m.Y H:i') }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Geöffnet von {{ $session->openedBy?->auditDisplayName() ?? 'Unbekannt' }} · geschlossen von {{ $session->closedBy?->auditDisplayName() ?? 'Unbekannt' }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Differenz</p>
                        <p class="text-2xl font-black {{ $difference === 0 ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ Money::format($difference, $currency) }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-7">
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Start</p><p class="mt-1 font-black">{{ Money::format((int) $session->opening_cash_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Barumsatz</p><p class="mt-1 font-black">{{ Money::format((int) $session->cash_sales_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Einlagen</p><p class="mt-1 font-black">{{ Money::format($deposits, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Entnahmen</p><p class="mt-1 font-black">{{ Money::format($withdrawals, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Soll</p><p class="mt-1 font-black">{{ Money::format((int) $session->closing_expected_cash_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Gezählt</p><p class="mt-1 font-black">{{ Money::format((int) $session->closing_counted_cash_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Differenz</p><p class="mt-1 font-black">{{ Money::format($difference, $currency) }}</p></div>
                </div>

                @if($session->closing_comment)
                    <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                        <span class="font-black">Abschlusskommentar:</span> {{ $session->closing_comment }}
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-3xl bg-white px-5 py-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">
                Keine abgeschlossenen Kassenschichten an diesem Tag.
            </div>
        @endforelse
    </section>
</main>
