@php use App\Support\Money; @endphp
<main class="admin-page admin-page--dense">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">V2 · Tickets</p>
            <h2 class="mt-1 text-3xl font-black">QR-Tickets</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">
                Tagesbezogene Menüberechtigungen ausgeben. Kostenlose Tickets erzeugen keinen Sale; bezahlte Tickets verweisen ausschließlich auf einen bereits abgeschlossenen Verkauf.
            </p>
        </div>
        @can('hospitality.configuration.manage')
            <a href="{{ route('administration.menus') }}" wire:navigate class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-900 shadow-sm transition hover:bg-slate-50">
                Menüs verwalten
            </a>
        @endcan
    </div>

    @if($notice)
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-bold text-emerald-900">{{ $notice }}</div>
    @endif
    @if($screenError)
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-bold text-red-900">{{ $screenError }}</div>
    @endif

    @if($issuedToken)
        <section class="mt-6 rounded-3xl border border-amber-300 bg-amber-50 p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-700">Nur einmal sichtbar</p>
                    <h3 class="mt-1 text-xl font-black text-amber-950">QR-Inhalt des neuen Tickets</h3>
                    <p class="mt-2 text-sm leading-6 text-amber-900/80">Diesen Wert direkt als QR-Inhalt verwenden oder sicher ausdrucken. Gespeichert wird ausschließlich sein kryptografischer Hash.</p>
                </div>
                <div class="min-w-0 rounded-2xl bg-white px-4 py-3 ring-1 ring-amber-300 lg:max-w-2xl">
                    <p class="break-all font-mono text-sm font-bold text-slate-950" data-testid="issued-ticket-token">{{ $issuedToken }}</p>
                </div>
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(360px,0.75fr)]">
        <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h3 class="text-xl font-black">Ticket ausgeben</h3>
                    <p class="mt-1 text-sm text-slate-500">Art, Gültigkeit und enthaltene Menüs festlegen.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black uppercase tracking-wide text-slate-600">{{ $menus->count() }} aktive Menüs</span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-bold">Art</span>
                    <select wire:model.live="fundingType" class="min-h-12 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10">
                        <option value="free">Kostenlos</option>
                        <option value="paid">Bezahlt · bestehender Sale</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-sm font-bold">Gültigkeitstag</span>
                    <input type="date" wire:model="validOn" class="min-h-12 w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10">
                </label>

                @if($fundingType === 'paid')
                    <label class="block sm:col-span-2">
                        <span class="mb-2 block text-sm font-bold">Sale-/Belegnummer</span>
                        <input type="text" wire:model="saleNumber" class="min-h-12 w-full rounded-2xl border border-slate-300 px-4 py-3 font-mono outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10" placeholder="z. B. 2026-000123">
                        <span class="mt-2 block text-xs leading-5 text-slate-500">V2-004 akzeptiert nur einen bereits abgeschlossenen, tatsächlich bezahlten Sale und erzeugt selbst keine Zahlung.</span>
                    </label>
                @endif
            </div>

            <div class="mt-7 border-t border-slate-100 pt-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h4 class="font-black">Menüberechtigungen</h4>
                        <p class="mt-1 text-sm text-slate-500">Menge 0 bedeutet: nicht im Ticket enthalten.</p>
                    </div>
                    @can('hospitality.configuration.manage')
                        <a href="{{ route('administration.menus') }}" wire:navigate class="text-sm font-black text-slate-700 underline decoration-slate-300 underline-offset-4 hover:text-slate-950">Menüs konfigurieren</a>
                    @endcan
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse($menus as $menu)
                        <label class="group grid cursor-pointer grid-cols-[1fr_112px] items-center gap-4 rounded-2xl border border-slate-200 p-4 transition hover:border-slate-400 hover:bg-slate-50">
                            <span class="min-w-0">
                                <span class="block truncate font-black text-slate-950">{{ $menu->name }}</span>
                                <span class="mt-1 block text-sm text-slate-500">{{ Money::format($menu->price_cents, config('kassensystem.currency', 'EUR')) }} · {{ $menu->groups_count }} Auswahlgruppe{{ $menu->groups_count === 1 ? '' : 'n' }}</span>
                            </span>
                            <span>
                                <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500">Menge</span>
                                <input type="number" min="0" max="999" wire:model="menuQuantities.{{ $menu->id }}" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-center font-black outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10" placeholder="0">
                            </span>
                        </label>
                    @empty
                        <div class="md:col-span-2 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-xl font-black ring-1 ring-slate-200">M</div>
                            <h4 class="mt-4 font-black text-slate-950">Noch keine aktiven Menüs</h4>
                            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-slate-600">Ticket-Berechtigungen beziehen sich auf Hospitality-Menüs, nicht direkt auf einzelne Katalogprodukte. Lege zuerst ein Menü mit mindestens einer Auswahlgruppe an.</p>
                            @can('hospitality.configuration.manage')
                                <a href="{{ route('administration.menus') }}" wire:navigate class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-slate-950 px-4 py-2 font-black text-white">Erstes Menü anlegen</a>
                            @endcan
                        </div>
                    @endforelse
                </div>
            </div>

            <button type="button" wire:click="issue" wire:loading.attr="disabled" @disabled($menus->isEmpty()) class="mt-6 min-h-12 w-full rounded-2xl bg-slate-950 px-5 py-3 font-black text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                <span wire:loading.remove wire:target="issue">Ticket ausgeben</span>
                <span wire:loading wire:target="issue">Ticket wird ausgegeben …</span>
            </button>
        </section>

        <aside class="rounded-3xl bg-white p-5 ring-1 ring-slate-200 sm:p-6">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Historie</p>
                <h3 class="mt-1 text-xl font-black">Letzte Tickets</h3>
                <p class="mt-1 text-sm text-slate-500">Die letzten 50 Ausgaben mit Status und Restmengen.</p>
            </div>

            <div class="mt-5 space-y-3">
                @forelse($tickets as $ticket)
                    <article class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-xs font-bold text-slate-500">#{{ $ticket->id }}</p>
                                <p class="mt-1 font-black">{{ $ticket->funding_type->value === 'paid' ? 'Bezahlt' : 'Kostenlos' }} · {{ $ticket->valid_on->format('d.m.Y') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-700">{{ $ticket->status->value }}</span>
                        </div>
                        @if($ticket->sale)
                            <p class="mt-2 font-mono text-xs text-slate-500">Sale {{ $ticket->sale->number }}</p>
                        @endif
                        <div class="mt-3 space-y-1 border-t border-slate-100 pt-3 text-sm">
                            @foreach($ticket->entitlements as $entitlement)
                                <div class="flex items-center justify-between gap-3">
                                    <span class="truncate text-slate-700">{{ $entitlement->menu_name_snapshot }}</span>
                                    <span class="shrink-0 font-black">{{ $entitlement->quantity_redeemed }}/{{ $entitlement->quantity_allowed }}</span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm text-slate-500">Noch keine Tickets ausgegeben.</div>
                @endforelse
            </div>
        </aside>
    </div>
</main>
