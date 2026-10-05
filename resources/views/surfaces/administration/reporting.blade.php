@php
    use App\Modules\CashRegister\Enums\CashMovementType;
    use App\Support\Money;
@endphp

<main class="admin-page admin-page--dense space-y-6">
    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Administration · V2</p>
                <h2 class="mt-2 text-3xl font-black">Berichte</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Verkäufe werden nach dem lokalen Verkaufstag in {{ $timezone }} ausgewertet. Stornos zählen zum Zeitpunkt der Gegenbuchung. Kassenschichten zählen zum Tag ihres tatsächlichen Abschlusses.
                </p>
            </div>

            <div class="text-right">
                <button wire:click="downloadCsv" type="button" class="rounded-xl bg-white px-4 py-2.5 text-sm font-black text-slate-950 hover:bg-slate-100">
                    Buchhaltungs-CSV herunterladen
                </button>
                <p class="mt-2 max-w-xs text-xs text-slate-400">Tabellenfreundliche V2-Auswertung; kein DATEV-/Steuerexport.</p>
            </div>
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

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Bruttoverkauf</p>
            <p class="mt-2 text-3xl font-black">{{ Money::format($summary['gross_sales_cents'], $currency) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $summary['sales_count'] }} Verkäufe · {{ $summary['free_sales_count'] }} kostenlos</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Storno</p>
            <p class="mt-2 text-3xl font-black text-red-700">{{ Money::format($summary['reversal_cents'], $currency) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ $summary['reversals_count'] }} Gegenbuchungen</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Netto-Umsatz</p>
            <p class="mt-2 text-3xl font-black">{{ Money::format($summary['net_revenue_cents'], $currency) }}</p>
            <p class="mt-1 text-xs text-slate-500">Bruttoverkauf minus Stornos des Berichtstags</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Rabatte</p>
            <p class="mt-2 text-3xl font-black">{{ Money::format($summary['discount_cents'], $currency) }}</p>
            <p class="mt-1 text-xs text-slate-500">Angebote {{ Money::format($summary['offer_discount_cents'], $currency) }} · manuell {{ Money::format($summary['manual_discount_cents'], $currency) }}</p>
        </article>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Barzahlungen</p>
            <p class="mt-2 text-2xl font-black">{{ Money::format($summary['cash_cents'], $currency) }}</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">PayPal.me</p>
            <p class="mt-2 text-2xl font-black">{{ Money::format($summary['paypal_cents'], $currency) }}</p>
        </article>
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold text-slate-500">Barauszahlungen aus Stornos</p>
            <p class="mt-2 text-2xl font-black">{{ Money::format($summary['cash_refund_cents'], $currency) }}</p>
        </article>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="text-lg font-black">Produktumsätze</h3>
            <p class="text-sm text-slate-500">Verkaufte Menge, historische Kategorie, Ausgangswert, Rabatt und Endumsatz aus Sale-Item-Snapshots.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Produkt</th>
                        <th class="px-5 py-3">Kategorie</th>
                        <th class="px-5 py-3 text-right">Menge</th>
                        <th class="px-5 py-3 text-right">Brutto</th>
                        <th class="px-5 py-3 text-right">Rabatt</th>
                        <th class="px-5 py-3 text-right sm:px-6">Umsatz</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($summary['products'] as $product)
                        <tr>
                            <td class="px-5 py-3 font-bold sm:px-6">{{ $product['product_name'] }}</td>
                            <td class="px-5 py-3">{{ $product['category_name'] }}</td>
                            <td class="px-5 py-3 text-right">{{ $product['quantity'] }}×</td>
                            <td class="px-5 py-3 text-right">{{ Money::format($product['gross_cents'], $currency) }}</td>
                            <td class="px-5 py-3 text-right">{{ Money::format($product['discount_cents'], $currency) }}</td>
                            <td class="px-5 py-3 text-right font-black sm:px-6">{{ Money::format($product['revenue_cents'], $currency) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Keine Verkäufe an diesem Tag.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-black">Kategorien</h3>
                <p class="text-sm text-slate-500">Historische Kategorien aus den Verkaufssnapshots.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($summary['categories'] as $category)
                    <div class="grid grid-cols-[1fr_auto] gap-4 px-5 py-3">
                        <div>
                            <p class="font-bold">{{ $category['category_name'] }}</p>
                            <p class="text-xs text-slate-500">{{ $category['quantity'] }}× · Rabatt {{ Money::format($category['discount_cents'], $currency) }}</p>
                        </div>
                        <p class="font-black">{{ Money::format($category['revenue_cents'], $currency) }}</p>
                    </div>
                @empty
                    <div class="p-6 text-center text-sm text-slate-500">Keine Kategorien.</div>
                @endforelse
            </div>
        </article>

        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-black">Gastro-Vorgänge</h3>
            <p class="mt-1 text-sm text-slate-500">Operative Vorgänge sind ausdrücklich kein Umsatz.</p>
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold text-slate-500">Geöffnet</p><p class="mt-1 text-2xl font-black">{{ $summary['hospitality']['opened_orders'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold text-slate-500">Geschlossen</p><p class="mt-1 text-2xl font-black">{{ $summary['hospitality']['closed_orders'] }}</p></div>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Kellner</p>
                    @forelse($summary['hospitality']['waiters'] as $waiter)
                        <p class="mt-2 text-sm"><span class="font-bold">{{ $waiter['name'] }}</span> · {{ $waiter['opened_orders'] }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Keine.</p>
                    @endforelse
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Bereiche</p>
                    @forelse($summary['hospitality']['areas'] as $area)
                        <p class="mt-2 text-sm"><span class="font-bold">{{ $area['area_name'] }}</span> · {{ $area['opened_orders'] }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Keine.</p>
                    @endforelse
                </div>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Tische</p>
                    @forelse($summary['hospitality']['tables'] as $table)
                        <p class="mt-2 text-sm"><span class="font-bold">{{ $table['area_name'] }} / {{ $table['table_name'] }}</span> · {{ $table['opened_orders'] }}</p>
                    @empty
                        <p class="mt-2 text-sm text-slate-500">Keine.</p>
                    @endforelse
                </div>
            </div>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-black">Tickets und Einlösungen</h3>
            <p class="mt-1 text-sm text-slate-500">Bezahlte Ticketkäufe bleiben normale Sales. Die spätere Einlösung wird nicht erneut als Umsatz gezählt.</p>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Ausgegeben</p><p class="mt-1 text-xl font-black">{{ $summary['tickets']['issued'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Bezahlt</p><p class="mt-1 text-xl font-black">{{ $summary['tickets']['paid'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Kostenlos</p><p class="mt-1 text-xl font-black">{{ $summary['tickets']['free'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Einlösungen</p><p class="mt-1 text-xl font-black">{{ $summary['tickets']['redemptions'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Menge</p><p class="mt-1 text-xl font-black">{{ $summary['tickets']['redeemed_quantity'] }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Abgedeckter Wert</p><p class="mt-1 text-xl font-black">{{ Money::format($summary['tickets']['covered_value_cents'], $currency) }}</p></div>
            </div>
        </article>

        <article class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h3 class="text-lg font-black">Zubereitungsstationen</h3>
            <p class="mt-1 text-sm text-slate-500">Statuszeiten des Berichtstags; Ø misst Start bis Fertig.</p>
            <div class="mt-4 space-y-3">
                @forelse($summary['preparation'] as $station)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-black">{{ $station['station_name'] }}</p>
                            <p class="text-sm font-bold">{{ $station['average_seconds'] === null ? '—' : $station['average_seconds'].' s Ø' }}</p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">Gestartet {{ $station['started_count'] }} · fertig {{ $station['ready_count'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Keine Stationsstatuswechsel an diesem Tag.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h3 class="text-lg font-black">Audit-Ereignisse</h3>
        <p class="mt-1 text-sm text-slate-500">Aggregierte Eventtypen. Actor, Referenzen und Payloads bleiben im geschützten Audit-Browser.</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @forelse($summary['audit'] as $event)
                <span class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold">{{ $event['event_key'] }} · {{ $event['count'] }}</span>
            @empty
                <span class="text-sm text-slate-500">Keine Audit-Ereignisse an diesem Tag.</span>
            @endforelse
        </div>
    </section>

    <section class="space-y-4">
        <div>
            <h3 class="text-xl font-black">Abgeschlossene Kassenschichten</h3>
            <p class="text-sm text-slate-500">Schichten werden nach ihrem tatsächlichen Abschlusszeitpunkt dem Berichtstag zugeordnet. Barumsatz und Barauszahlungen stammen aus dem Kassenschicht-Ledger.</p>
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

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-8">
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Start</p><p class="mt-1 font-black">{{ Money::format((int) $session->opening_cash_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Barumsatz</p><p class="mt-1 font-black">{{ Money::format((int) $session->cash_sales_cents, $currency) }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-3"><p class="text-xs font-bold text-slate-500">Barstornos</p><p class="mt-1 font-black">{{ Money::format((int) $session->cash_refunds_cents, $currency) }}</p></div>
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
