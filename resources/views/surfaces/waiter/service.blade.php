@php use App\Support\Money; @endphp
<div class="min-h-[100dvh] bg-black text-white" wire:poll.5s>
    <header class="sticky top-0 z-30 border-b border-white/10 bg-black/95 px-4 py-3 backdrop-blur" style="padding-top: max(.75rem, env(safe-area-inset-top));">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-white/50">Kellner-POS</p>
                <h1 class="text-xl font-black">{{ $user->auditDisplayName() }}</h1>
            </div>
            <div class="flex gap-2">
                @if($canUseRegister)
                    <a href="{{ route('pos.register') }}" wire:navigate class="flex min-h-12 items-center rounded-xl border border-white/20 px-4 text-sm font-black">Kasse</a>
                @endif
                <button type="button" wire:click="switchUser" class="min-h-12 rounded-xl bg-white px-4 text-sm font-black text-black">Benutzer wechseln</button>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1600px] space-y-5 p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
        @if($notice)
            <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 font-bold text-emerald-100">{{ $notice }}</div>
        @endif
        @if($screenError)
            <div class="rounded-2xl border border-red-400/30 bg-red-400/10 px-4 py-3 font-bold text-red-100">{{ $screenError }}</div>
        @endif

        <nav class="flex gap-2 overflow-x-auto pb-1">
            @foreach($areas as $area)
                <button
                    type="button"
                    wire:click="selectArea({{ $area->id }})"
                    class="min-h-12 shrink-0 rounded-xl px-5 text-sm font-black {{ $selectedAreaId === $area->id ? 'bg-white text-black' : 'border border-white/15 bg-white/5 text-white' }}"
                >
                    {{ $area->name }}
                </button>
            @endforeach
        </nav>

        <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <section>
                @forelse($areas->where('id', $selectedAreaId) as $area)
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                        @foreach($area->tables as $table)
                            @php($occupied = $table->openOrder !== null)
                            <button
                                type="button"
                                wire:click="selectTable({{ $table->id }})"
                                class="min-h-[112px] rounded-2xl border p-4 text-left transition active:scale-[.98] {{ $selectedTableId === $table->id ? 'border-white bg-white text-black' : ($occupied ? 'border-amber-300/40 bg-amber-300/10' : 'border-white/15 bg-white/5') }}"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-lg font-black">{{ $table->name }}</span>
                                    <span class="rounded-lg px-2 py-1 text-[11px] font-black uppercase tracking-wide {{ $occupied ? 'bg-amber-300 text-black' : 'bg-emerald-400 text-black' }}">
                                        {{ $occupied ? 'Belegt' : 'Frei' }}
                                    </span>
                                </div>
                                @if($occupied)
                                    <p class="mt-3 font-mono text-xs font-bold opacity-70">{{ $table->openOrder->number }}</p>
                                    <p class="mt-1 text-xl font-black">{{ Money::format($table->openOrder->total_cents, $currency) }}</p>
                                @else
                                    <p class="mt-4 text-sm opacity-60">Antippen zum Öffnen</p>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @empty
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-10 text-center text-white/60">Noch keine aktiven Bereiche oder Tische.</div>
                @endforelse

                @if($selectedOrder)
                    <section class="mt-5 rounded-3xl border border-white/10 bg-white/5 p-4 sm:p-5">
                        <div class="flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Produkte &amp; Menüs</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $selectedOrder->table->name }}</h2>
                            </div>
                            <div class="font-mono text-sm font-bold text-white/60">{{ $selectedOrder->number }}</div>
                        </div>

                        <div class="mt-5 space-y-5">
                            @foreach($catalog as $category)
                                <div>
                                    <h3 class="mb-2 text-sm font-black uppercase tracking-wide text-white/60">{{ $category->name }}</h3>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                        @foreach($category->products as $product)
                                            <button type="button" wire:click="chooseProduct({{ $product->id }})" class="min-h-[72px] rounded-2xl border border-white/10 bg-white/5 p-3 text-left active:bg-white/10">
                                                <span class="block font-black">{{ $product->short_name }}</span>
                                                <span class="mt-1 block text-sm text-white/60">{{ Money::format($product->price_cents, $currency) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            @if($menus->isNotEmpty())
                                <div>
                                    <h3 class="mb-2 text-sm font-black uppercase tracking-wide text-white/60">Menüs</h3>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                        @foreach($menus as $menu)
                                            <button type="button" wire:click="chooseMenu({{ $menu->id }})" class="min-h-[72px] rounded-2xl border border-cyan-300/20 bg-cyan-300/10 p-3 text-left active:bg-cyan-300/20">
                                                <span class="block font-black">{{ $menu->name }}</span>
                                                <span class="mt-1 block text-sm text-cyan-100/70">{{ Money::format($menu->price_cents, $currency) }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>
                @endif
            </section>

            <aside class="rounded-3xl border border-white/10 bg-white/5 p-4 sm:p-5 xl:sticky xl:top-24 xl:self-start">
                @if($openingTable && $selectedTableId)
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Freier Tisch</p>
                    <h2 class="mt-1 text-2xl font-black">Vorgang öffnen</h2>
                    <textarea wire:model="orderNote" rows="3" maxlength="500" placeholder="Tischnotiz (optional)" class="mt-5 w-full rounded-2xl border border-white/15 bg-black px-4 py-3 text-white placeholder:text-white/30"></textarea>
                    <button type="button" wire:click="openSelectedTable" class="mt-3 min-h-14 w-full rounded-2xl bg-white px-5 font-black text-black">Tisch belegen</button>
                @elseif($selectedOrder)
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">{{ $selectedOrder->area_name_snapshot }}</p>
                            <h2 class="mt-1 text-2xl font-black">{{ $selectedOrder->table_name_snapshot }}</h2>
                            <p class="mt-1 font-mono text-xs text-white/50">{{ $selectedOrder->number }}</p>
                        </div>
                        <p class="text-xl font-black">{{ Money::format($selectedOrder->total_cents, $currency) }}</p>
                    </div>

                    @if($selectedOrder->note)
                        <div class="mt-4 rounded-2xl border border-amber-300/20 bg-amber-300/10 p-3 text-sm text-amber-100">
                            {{ $selectedOrder->note }}
                        </div>
                    @endif

                    <div class="mt-5 divide-y divide-white/10">
                        @forelse($selectedOrder->items as $item)
                            <div class="py-4">
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <p class="font-black">{{ $item->quantity }} × {{ $item->label }}</p>
                                        @foreach($item->components as $component)
                                            <p class="mt-1 text-xs text-white/50">{{ $component->menu_group_name }}: {{ $component->product_name }}</p>
                                        @endforeach
                                        @foreach($item->options as $option)
                                            <p class="mt-1 text-xs text-white/50">{{ $option->option_group_name }}: {{ $option->option_value_name }}</p>
                                        @endforeach
                                        @if($item->note)
                                            <p class="mt-2 text-xs font-bold text-amber-200">Notiz: {{ $item->note }}</p>
                                        @endif
                                    </div>
                                    <span class="whitespace-nowrap font-black">{{ Money::format($item->total_cents, $currency) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-white/50">Noch keine Positionen.</p>
                        @endforelse
                    </div>

                    <div class="mt-5 rounded-2xl border border-white/10 bg-black p-4 text-sm text-white/60">
                        Zahlung und Tischabschluss werden in den kommenden Ticket-/Payment-Blöcken ergänzt. Ein offener Vorgang erzeugt weiterhin keinen Umsatz.
                    </div>
                @else
                    <div class="py-10 text-center">
                        <p class="text-lg font-black">Tisch auswählen</p>
                        <p class="mt-2 text-sm text-white/50">Freie Tische öffnen oder belegte Vorgänge gemeinsam weiterbearbeiten.</p>
                    </div>
                @endif
            </aside>
        </div>
    </main>

    @if($selectedProduct)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/80 p-0 sm:items-center sm:p-4">
            <div class="max-h-[92dvh] w-full max-w-xl overflow-y-auto rounded-t-3xl border border-white/10 bg-zinc-950 p-5 sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Produkt</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $selectedProduct->name }}</h2>
                    </div>
                    <button type="button" wire:click="closeItemDialog" class="min-h-12 rounded-xl border border-white/15 px-4 font-black">Schließen</button>
                </div>

                @foreach($optionGroups as $group)
                    <fieldset class="mt-5">
                        <legend class="font-black">{{ $group->name }} <span class="text-xs font-normal text-white/50">({{ $group->min_choices }}–{{ $group->max_choices }})</span></legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach($group->values as $value)
                                <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4">
                                    <input wire:model="selectedOptionValueIds" type="checkbox" value="{{ $value->id }}" class="h-5 w-5">
                                    <span class="flex-1 font-bold">{{ $value->name }}</span>
                                    @if($value->price_delta_cents > 0)
                                        <span class="text-sm text-white/60">+{{ Money::format($value->price_delta_cents, $currency) }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <label class="mt-5 block">
                    <span class="text-sm font-black">Freitext / Zubereitungshinweis</span>
                    <textarea wire:model="itemNote" rows="3" maxlength="500" class="mt-2 w-full rounded-2xl border border-white/15 bg-black px-4 py-3" placeholder="z. B. ohne Sauce"></textarea>
                </label>

                <button type="button" wire:click="addSelectedProduct" class="mt-5 min-h-14 w-full rounded-2xl bg-white px-5 font-black text-black">Position hinzufügen</button>
            </div>
        </div>
    @endif

    @if($selectedMenu)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/80 p-0 sm:items-center sm:p-4">
            <div class="max-h-[92dvh] w-full max-w-2xl overflow-y-auto rounded-t-3xl border border-white/10 bg-zinc-950 p-5 sm:rounded-3xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Menü</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $selectedMenu->name }}</h2>
                    </div>
                    <button type="button" wire:click="closeItemDialog" class="min-h-12 rounded-xl border border-white/15 px-4 font-black">Schließen</button>
                </div>

                @foreach($selectedMenu->groups as $group)
                    <fieldset class="mt-5">
                        <legend class="font-black">{{ $group->name }} <span class="text-xs font-normal text-white/50">({{ $group->min_choices }}–{{ $group->max_choices }})</span></legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach($group->products as $entry)
                                <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4">
                                    <input wire:model="menuSelections.{{ $group->id }}" type="checkbox" value="{{ $entry->product_id }}" class="h-5 w-5">
                                    <span class="flex-1 font-bold">{{ $entry->product->name }}</span>
                                    @if($entry->price_delta_cents > 0)
                                        <span class="text-sm text-white/60">+{{ Money::format($entry->price_delta_cents, $currency) }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <label class="mt-5 block">
                    <span class="text-sm font-black">Freitext / Zubereitungshinweis</span>
                    <textarea wire:model="menuNote" rows="3" maxlength="500" class="mt-2 w-full rounded-2xl border border-white/15 bg-black px-4 py-3"></textarea>
                </label>

                <button type="button" wire:click="addSelectedMenu" class="mt-5 min-h-14 w-full rounded-2xl bg-white px-5 font-black text-black">Menü hinzufügen</button>
            </div>
        </div>
    @endif
</div>
