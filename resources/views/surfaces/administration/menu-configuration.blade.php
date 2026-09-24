@php use App\Support\Money; @endphp
<main class="admin-page admin-page--dense">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Gastro · Menüs</p>
            <h2 class="mt-1 text-3xl font-black">Menü-Konfiguration</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Menüs bestehen aus einem Grundpreis und Auswahlgruppen. Die hier aktiven Menüs stehen im Kellner-POS und bei QR-Tickets zur Verfügung.</p>
        </div>
        <a href="{{ route('administration.tickets') }}" wire:navigate class="inline-flex min-h-12 items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-900 shadow-sm transition hover:bg-slate-50">Zu den Tickets</a>
    </div>

    @if($notice)<div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
    @if($screenError)<div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-bold text-red-900">{{ $screenError }}</div>@endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <div class="space-y-5">
            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Schritt 1</p>
                <h3 class="mt-1 font-black">Menü anlegen</h3>
                <div class="mt-4 space-y-3">
                    <input wire:model="menuName" placeholder="Name, z. B. Tagesmenü" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10">
                    <input wire:model="menuPrice" inputmode="decimal" placeholder="Grundpreis, z. B. 6,50" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10">
                    <input wire:model="menuSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10">
                    <button wire:click="createMenu" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Menü anlegen</button>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Schritt 2</p>
                <h3 class="mt-1 font-black">Auswahlgruppe anlegen</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Beispiel: „Hauptgericht“, mindestens 1 und höchstens 1 Auswahl.</p>
                <div class="mt-4 space-y-3">
                    <select wire:model="groupMenuId" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        <option value="">Aktives Menü auswählen</option>
                        @foreach($menus->where('active', true) as $menu)
                            <option value="{{ $menu->id }}">{{ $menu->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="groupName" placeholder="Gruppenname" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-xs font-bold text-slate-500">Minimum<input wire:model="groupMinChoices" type="number" min="0" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                        <label class="text-xs font-bold text-slate-500">Maximum<input wire:model="groupMaxChoices" type="number" min="1" class="mt-1 min-h-11 w-full rounded-xl border border-slate-300 px-3"></label>
                    </div>
                    <input wire:model="groupSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <button wire:click="createGroup" @disabled($menus->where('active', true)->isEmpty()) class="min-h-12 w-full rounded-xl bg-slate-800 px-4 py-3 font-black text-white disabled:bg-slate-300">Gruppe anlegen</button>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Schritt 3</p>
                <h3 class="mt-1 font-black">Produkt zuweisen</h3>
                <div class="mt-4 space-y-3">
                    <select wire:model="productGroupId" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        <option value="">Auswahlgruppe</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->menu->name }} · {{ $group->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="productId" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        <option value="">Produkt</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->category->name }} · {{ $product->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="productPriceDelta" inputmode="decimal" placeholder="Aufpreis, z. B. 0,50" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <input wire:model="productSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <button wire:click="addProduct" @disabled($groups->isEmpty() || $products->isEmpty()) class="min-h-12 w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white disabled:bg-slate-300">Produkt hinzufügen</button>
                </div>
            </section>
        </div>

        <div class="space-y-4">
            @forelse($menus as $menu)
                <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-black">{{ $menu->name }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $menu->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $menu->active ? 'aktiv' : 'inaktiv' }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">Grundpreis {{ Money::format($menu->price_cents, $currency) }} · Sortierung {{ $menu->sort_order }}</p>
                        </div>
                        @if(! $menu->active)
                            <button wire:click="activateMenu({{ $menu->id }})" class="min-h-11 rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white">Aktivieren</button>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @forelse($menu->groups as $group)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $group->name }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Auswahl {{ $group->min_choices }}–{{ $group->max_choices }}</p>
                                    </div>
                                    <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-600">{{ $group->products->where('active', true)->count() }} Produkte</span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    @forelse($group->products as $entry)
                                        <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-sm {{ $entry->active ? '' : 'opacity-50' }}">
                                            <span class="min-w-0 truncate font-semibold">{{ $entry->product->name }}</span>
                                            <span class="shrink-0 text-xs font-bold text-slate-500">+ {{ Money::format($entry->price_delta_cents, $currency) }}</span>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500">Noch keine Produkte zugewiesen.</p>
                                    @endforelse
                                </div>
                            </div>
                        @empty
                            <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">Noch keine Auswahlgruppe. Ohne Gruppe kann das Menü zwar bestehen, für den Kellner- und Ticketablauf sollte mindestens eine passende Gruppe mit Produkten gepflegt werden.</div>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center ring-1 ring-slate-100">
                    <h3 class="font-black">Noch keine Hospitality-Menüs</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">Katalogprodukte sind bereits vorhanden, aber Ticket-Berechtigungen benötigen ein eigenes Menü-Bündel. Lege links zuerst dein erstes Menü an.</p>
                </div>
            @endforelse
        </div>
    </div>
</main>
