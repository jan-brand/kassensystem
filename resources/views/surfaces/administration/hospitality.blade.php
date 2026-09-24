<main class="admin-page admin-page--dense">
    <div>
        <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Gastro-Konfiguration</p>
        <h2 class="mt-1 text-3xl font-black">Bereiche &amp; Tische</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">V2 verwendet eine touchoptimierte Kachelübersicht. Ein grafischer Raumplan bleibt für V4 reserviert.</p>
    </div>

    @if($notice)<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
    @if($screenError)<div class="mt-4 rounded-xl bg-red-50 p-3 font-bold text-red-900">{{ $screenError }}</div>@endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[360px_1fr]">
        <div class="space-y-5">
            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <h3 class="font-black">Bereich anlegen</h3>
                <div class="mt-3 space-y-2">
                    <input wire:model="areaName" placeholder="z. B. Innenraum" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <input wire:model="areaSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <button wire:click="createArea" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Bereich anlegen</button>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <h3 class="font-black">Tisch anlegen</h3>
                <div class="mt-3 space-y-2">
                    <select wire:model="tableAreaId" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                        <option value="">Bereich</option>
                        @foreach($areas->where('active', true) as $area)
                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                        @endforeach
                    </select>
                    <input wire:model="tableName" placeholder="z. B. Tisch 4" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <input wire:model="tableSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <button wire:click="createTable" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Tisch anlegen</button>
                </div>
            </section>
        </div>

        <div class="space-y-4">
            @forelse($areas as $area)
                <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-xl font-black">{{ $area->name }} @if(!$area->active)<span class="text-sm text-slate-400">(inaktiv)</span>@endif</h3>
                            <p class="text-sm text-slate-500">Sortierung {{ $area->sort_order }} · {{ $area->tables->count() }} Tische</p>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="startEditArea({{ $area->id }})" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Bearbeiten</button>
                            <button wire:click="toggleArea({{ $area->id }})" class="min-h-12 rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">{{ $area->active ? 'Deaktivieren' : 'Aktivieren' }}</button>
                        </div>
                    </div>

                    @if($editingAreaId === $area->id)
                        <div class="mt-3 grid gap-2 sm:grid-cols-[1fr_120px_auto]">
                            <input wire:model="editAreaName" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2">
                            <input wire:model="editAreaSortOrder" type="number" min="0" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2">
                            <button wire:click="saveArea" class="min-h-12 rounded-xl bg-slate-950 px-4 py-2 font-bold text-white">Speichern</button>
                        </div>
                    @endif

                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                        @forelse($area->tables as $table)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                @if($editingTableId === $table->id)
                                    <div class="space-y-2">
                                        <select wire:model="editTableAreaId" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                                            @foreach($areas->where('active', true) as $targetArea)
                                                <option value="{{ $targetArea->id }}">{{ $targetArea->name }}</option>
                                            @endforeach
                                        </select>
                                        <input wire:model="editTableName" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                                        <input wire:model="editTableSortOrder" type="number" min="0" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                                        <button wire:click="saveTable" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-2 font-bold text-white">Speichern</button>
                                    </div>
                                @else
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-black">{{ $table->name }}</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ $table->active ? 'aktiv' : 'inaktiv' }}
                                                · {{ $table->openOrder ? 'belegt · '.$table->openOrder->number : 'frei' }}
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button wire:click="startEditTable({{ $table->id }})" class="min-h-12 rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Bearbeiten</button>
                                            <button wire:click="toggleTable({{ $table->id }})" class="min-h-12 rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">{{ $table->active ? 'Aus' : 'An' }}</button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Noch keine Tische.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <div class="rounded-3xl bg-white p-8 text-center text-slate-500 ring-1 ring-slate-200">Noch keine Bereiche.</div>
            @endforelse
        </div>
    </div>
</main>
