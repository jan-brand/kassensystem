<main class="admin-page admin-page--dense">
<h2 class="text-3xl font-black">Kassierer</h2>
@if($notice)<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
@if($screenError)<div class="mt-4 rounded-xl bg-red-50 p-3 font-bold text-red-900">{{ $screenError }}</div>@endif
<div class="mt-6 grid gap-6 xl:grid-cols-[340px_1fr]">
<section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
<h3 class="font-black">Kassierer anlegen</h3>
<div class="mt-3 space-y-2">
<input wire:model="username" placeholder="Benutzername" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="firstName" placeholder="Vorname" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="lastName" placeholder="Nachname" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="email" type="email" placeholder="E-Mail (optional)" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="phone" placeholder="Telefon (optional)" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="pin" type="password" inputmode="numeric" maxlength="6" placeholder="6-stellige PIN" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="createCashier" class="w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Anlegen</button>
</div>
</section>
<section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
<div class="divide-y divide-slate-100">
@forelse($cashiers as $cashier)
<div class="py-4">
@if($editingId === $cashier->id)
<div class="grid gap-2 sm:grid-cols-2">
<input wire:model="editUsername" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editDisplayName" placeholder="Anzeigename" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editFirstName" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editLastName" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editEmail" type="email" placeholder="E-Mail" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editPhone" placeholder="Telefon" class="rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="saveCashier" class="rounded-xl bg-slate-950 px-4 py-2 font-bold text-white sm:col-span-2">Speichern</button>
</div>
@else
<div class="flex flex-wrap items-start justify-between gap-3">
<div><p class="font-black">{{ $cashier->auditDisplayName() }}</p><p class="text-sm text-slate-500">{{ $cashier->username }} · {{ $cashier->active ? 'aktiv' : 'inaktiv' }}</p></div>
<div class="flex flex-wrap gap-2"><button wire:click="startEdit({{ $cashier->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Bearbeiten</button><button wire:click="startPinReset({{ $cashier->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">PIN</button><button wire:click="toggleActive({{ $cashier->id }})" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">{{ $cashier->active ? 'Deaktivieren' : 'Aktivieren' }}</button></div>
</div>
@endif
@if($resetPinId === $cashier->id)
<div class="mt-3 flex gap-2"><input wire:model="newPin" type="password" inputmode="numeric" maxlength="6" placeholder="Neue PIN" class="flex-1 rounded-xl border border-amber-300 px-3 py-2"><button wire:click="resetPin" class="rounded-xl bg-amber-600 px-4 py-2 font-bold text-white">Speichern</button></div>
@endif
</div>
@empty<div class="py-6 text-center text-slate-500">Noch keine Kassierer.</div>@endforelse
</div>
</section>
</div>
</main>
