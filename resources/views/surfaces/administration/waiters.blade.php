<main class="admin-page admin-page--dense">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Mitarbeiter</p>
        <h2 class="mt-1 text-3xl font-black">Kellner</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Eigene Rolle für Tisch- und Bestellbetrieb. Kassenrechte werden dadurch nicht automatisch vergeben.</p>
    </div>

    @if($notice)<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
    @if($screenError)<div class="mt-4 rounded-xl bg-red-50 p-3 font-bold text-red-900">{{ $screenError }}</div>@endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[340px_1fr]">
        <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
            <h3 class="font-black">Kellner anlegen</h3>
            <div class="mt-3 space-y-2">
                <input wire:model="username" placeholder="Benutzername" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                <input wire:model="firstName" placeholder="Vorname" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                <input wire:model="lastName" placeholder="Nachname" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                <input wire:model="displayName" placeholder="Anzeigename (optional)" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                <input wire:model="pin" type="password" inputmode="numeric" maxlength="6" placeholder="6-stellige PIN" class="w-full rounded-xl border border-slate-300 px-3 py-3">
                <button wire:click="createWaiter" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Anlegen</button>
            </div>
        </section>

        <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
            <div class="divide-y divide-slate-100">
                @forelse($waiters as $waiter)
                    <div class="py-4">
                        @if($editingId === $waiter->id)
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input wire:model="editUsername" class="rounded-xl border border-slate-300 px-3 py-3">
                                <input wire:model="editDisplayName" placeholder="Anzeigename" class="rounded-xl border border-slate-300 px-3 py-3">
                                <input wire:model="editFirstName" class="rounded-xl border border-slate-300 px-3 py-3">
                                <input wire:model="editLastName" class="rounded-xl border border-slate-300 px-3 py-3">
                                <button wire:click="saveWaiter" class="min-h-12 rounded-xl bg-slate-950 px-4 py-2 font-bold text-white sm:col-span-2">Speichern</button>
                            </div>
                        @else
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-black">{{ $waiter->auditDisplayName() }}</p>
                                    <p class="text-sm text-slate-500">{{ $waiter->username }} · {{ $waiter->active ? 'aktiv' : 'inaktiv' }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="startEdit({{ $waiter->id }})" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Bearbeiten</button>
                                    <button wire:click="startPinReset({{ $waiter->id }})" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">PIN</button>
                                    <button wire:click="toggleActive({{ $waiter->id }})" class="min-h-12 rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">{{ $waiter->active ? 'Deaktivieren' : 'Aktivieren' }}</button>
                                </div>
                            </div>
                        @endif

                        @if($resetPinId === $waiter->id)
                            <div class="mt-3 flex gap-2">
                                <input wire:model="newPin" type="password" inputmode="numeric" maxlength="6" placeholder="Neue PIN" class="min-h-12 flex-1 rounded-xl border border-amber-300 px-3 py-2">
                                <button wire:click="resetPin" class="min-h-12 rounded-xl bg-amber-600 px-4 py-2 font-bold text-white">Speichern</button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="py-6 text-center text-slate-500">Noch keine Kellner.</div>
                @endforelse
            </div>
        </section>
    </div>
</main>
