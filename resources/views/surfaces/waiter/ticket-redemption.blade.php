<div class="min-h-[100dvh] bg-black text-white">
    <header class="border-b border-white/10 px-4 py-4">
        <div class="mx-auto flex max-w-[1200px] items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-white/50">Kellner-POS</p>
                <h1 class="text-2xl font-black">QR-Tickets</h1>
            </div>
            <button type="button" wire:click="switchUser" class="min-h-12 rounded-xl bg-white px-4 text-sm font-black text-black">Benutzer wechseln</button>
        </div>
    </header>

    <main class="mx-auto max-w-[1200px] space-y-5 p-4">
        @if($notice)
            <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 font-bold text-emerald-100">{{ $notice }}</div>
        @endif
        @if($screenError)
            <div class="rounded-2xl border border-red-400/30 bg-red-400/10 px-4 py-3 font-bold text-red-100">{{ $screenError }}</div>
        @endif

        <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
            <label class="block">
                <span class="text-sm font-black">QR-Inhalt erfassen</span>
                <div class="mt-2 flex gap-2">
                    <input autofocus autocomplete="off" type="text" wire:model="token" wire:keydown.enter="scan" class="min-h-14 min-w-0 flex-1 rounded-2xl border border-white/15 bg-black px-4 font-mono text-white" placeholder="QR scannen oder Token eingeben">
                    <button type="button" wire:click="scan" class="min-h-14 rounded-2xl bg-white px-5 font-black text-black">Erfassen</button>
                </div>
            </label>
        </section>

        @if($ticket)
            <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Ticket #{{ $ticket->id }}</p>
                        <h2 class="mt-1 text-2xl font-black">{{ $ticket->funding_type->value === 'paid' ? 'Bezahlt' : 'Kostenlos' }}</h2>
                        <p class="mt-1 text-sm text-white/60">Gültig {{ $ticket->valid_on->format('d.m.Y') }} · Status {{ $ticket->status->value }}</p>
                    </div>
                    @if($ticket->sale)
                        <span class="rounded-xl border border-white/15 px-3 py-2 font-mono text-sm">Beleg {{ $ticket->sale->number }}</span>
                    @endif
                </div>

                @if($ticket->assignedOrder)
                    <div class="mt-4 rounded-2xl border border-amber-300/30 bg-amber-300/10 p-4">
                        <p class="font-black">Dauerhaft zugeordnet: {{ $ticket->assignedOrder->area_name_snapshot }} / {{ $ticket->assignedOrder->table_name_snapshot }}</p>
                        <p class="mt-1 font-mono text-xs text-white/60">{{ $ticket->assignedOrder->number }}</p>
                    </div>
                @else
                    <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($openOrders as $order)
                            <button type="button" wire:click="selectOrder({{ $order->id }})" class="min-h-16 rounded-2xl border p-3 text-left {{ $selectedOrderId === $order->id ? 'border-white bg-white text-black' : 'border-white/15 bg-black' }}">
                                <span class="block font-black">{{ $order->area_name_snapshot }} · {{ $order->table_name_snapshot }}</span>
                                <span class="mt-1 block font-mono text-xs opacity-60">{{ $order->number }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button type="button" wire:click="assign" class="mt-4 min-h-14 rounded-2xl bg-white px-5 font-black text-black">Ticket diesem Tisch zuordnen</button>
                @endif

                @if($ticket->assigned_order_id)
                    <div class="mt-6 grid gap-3 md:grid-cols-2">
                        @foreach($ticket->entitlements as $entitlement)
                            <button type="button" wire:click="chooseEntitlement({{ $entitlement->id }})" @disabled($entitlement->remainingQuantity() < 1) class="min-h-20 rounded-2xl border border-cyan-300/20 bg-cyan-300/10 p-4 text-left disabled:opacity-40">
                                <span class="block font-black">{{ $entitlement->menu_name_snapshot }}</span>
                                <span class="mt-1 block text-sm text-cyan-100/70">Verfügbar: {{ $entitlement->remainingQuantity() }} von {{ $entitlement->quantity_allowed }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        @if($selectedEntitlement)
            <section class="rounded-3xl border border-cyan-300/20 bg-cyan-300/5 p-5">
                <h2 class="text-xl font-black">{{ $selectedEntitlement->menu_name_snapshot }} einlösen</h2>
                <label class="mt-4 block max-w-xs">
                    <span class="text-sm font-black">Menge</span>
                    <input type="number" min="1" max="{{ $selectedEntitlement->remainingQuantity() }}" wire:model="redemptionQuantity" class="mt-2 min-h-12 w-full rounded-xl border border-white/15 bg-black px-4">
                </label>

                @foreach($selectedEntitlement->menu->groups as $group)
                    <fieldset class="mt-5">
                        <legend class="font-black">{{ $group->name }} <span class="text-xs font-normal text-white/50">({{ $group->min_choices }}–{{ $group->max_choices }})</span></legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach($group->products as $entry)
                                <label class="flex min-h-14 cursor-pointer items-center gap-3 rounded-2xl border border-white/10 bg-black px-4">
                                    <input wire:model="menuSelections.{{ $group->id }}" type="checkbox" value="{{ $entry->product_id }}" class="h-5 w-5">
                                    <span class="flex-1 font-bold">{{ $entry->product->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <label class="mt-5 block">
                    <span class="text-sm font-black">Freitext / Zubereitungshinweis</span>
                    <textarea wire:model="note" rows="3" maxlength="500" class="mt-2 w-full rounded-2xl border border-white/15 bg-black px-4 py-3"></textarea>
                </label>

                <button type="button" wire:click="redeem" class="mt-5 min-h-14 w-full rounded-2xl bg-cyan-200 px-5 font-black text-black">Berechtigung einlösen</button>
            </section>
        @endif
    </main>
</div>
