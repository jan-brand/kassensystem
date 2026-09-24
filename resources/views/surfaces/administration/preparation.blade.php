<main class="admin-page admin-page--dense">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Gastro · Zubereitung</p>
            <h2 class="mt-1 text-3xl font-black">Stationen &amp; Routing</h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-600">Produkte können mehreren Stationen zugeordnet werden. Dieselbe Zuordnung gilt automatisch, wenn das Produkt Bestandteil eines Menüs ist.</p>
        </div>
        <a href="{{ route('preparation.display') }}" class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white">Stationsdisplay öffnen</a>
    </div>

    @if($notice)<div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
    @if($screenError)<div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-bold text-red-900">{{ $screenError }}</div>@endif

    <div class="mt-6 grid gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <div class="space-y-5">
            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <h3 class="font-black">Station anlegen</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Beispiele: Küche, Bar, Dessert, Ausgabe.</p>
                <div class="mt-4 space-y-3">
                    <input wire:model="stationName" placeholder="Stationsname" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <input wire:model="stationCode" placeholder="Code optional, z. B. kueche" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <select wire:model="stationSound" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        @foreach($sounds as $sound)
                            <option value="{{ $sound->value }}">{{ match($sound->value) { 'none' => 'Kein Ton', 'chime' => 'Zweiklang', default => 'Glocke' } }}</option>
                        @endforeach
                    </select>
                    <input wire:model="stationSortOrder" type="number" min="0" placeholder="Sortierung" class="min-h-12 w-full rounded-xl border border-slate-300 px-3 py-2">
                    <button wire:click="createStation" class="min-h-12 w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Station anlegen</button>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
                <h3 class="font-black">Produkt routen</h3>
                <p class="mt-1 text-xs leading-5 text-slate-500">Bereits erzeugte Arbeitsaufträge bleiben bei späterem Entfernen einer Zuordnung erhalten.</p>
                <div class="mt-4 space-y-3">
                    <select wire:model="assignmentStationId" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        <option value="">Station</option>
                        @foreach($stations as $station)<option value="{{ $station->id }}">{{ $station->name }}</option>@endforeach
                    </select>
                    <select wire:model="assignmentProductId" class="min-h-12 w-full rounded-xl border border-slate-300 bg-white px-3 py-2">
                        <option value="">Produkt</option>
                        @foreach($products as $product)<option value="{{ $product->id }}">{{ $product->category->name }} · {{ $product->name }}</option>@endforeach
                    </select>
                    <button wire:click="assignProduct" @disabled($stations->isEmpty() || $products->isEmpty()) class="min-h-12 w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white disabled:bg-slate-300">Zuordnen</button>
                </div>
            </section>
        </div>

        <div class="space-y-4">
            @forelse($stations as $station)
                <section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200 sm:p-6">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-xl font-black">{{ $station->name }}</h3>
                                <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $station->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $station->active ? 'aktiv' : 'inaktiv' }}</span>
                            </div>
                            <p class="mt-1 font-mono text-xs text-slate-500">/preparation?station={{ $station->code }}</p>
                        </div>
                        <a href="{{ route('preparation.display', ['station' => $station->code]) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-black">Display</a>
                    </div>

                    <div class="mt-5 grid gap-3 md:grid-cols-[1fr_140px_170px_auto]">
                        <input wire:model="stationNames.{{ $station->id }}" class="min-h-11 rounded-xl border border-slate-300 px-3">
                        <input wire:model="stationSortOrders.{{ $station->id }}" type="number" min="0" class="min-h-11 rounded-xl border border-slate-300 px-3">
                        <select wire:model="stationSounds.{{ $station->id }}" class="min-h-11 rounded-xl border border-slate-300 bg-white px-3">
                            @foreach($sounds as $sound)<option value="{{ $sound->value }}">{{ match($sound->value) { 'none' => 'Kein Ton', 'chime' => 'Zweiklang', default => 'Glocke' } }}</option>@endforeach
                        </select>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-2 text-sm font-bold"><input wire:model="stationActive.{{ $station->id }}" type="checkbox" class="h-5 w-5"> Aktiv</label>
                            <button wire:click="saveStation({{ $station->id }})" class="min-h-11 rounded-xl bg-slate-950 px-4 text-sm font-black text-white">Speichern</button>
                        </div>
                    </div>

                    <div class="mt-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Zugeordnete Produkte</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($station->products as $product)
                                <button wire:click="removeProduct({{ $station->id }}, {{ $product->id }})" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-left text-sm font-bold hover:bg-red-50">
                                    {{ $product->name }} <span class="font-normal text-slate-400">×</span>
                                </button>
                            @empty
                                <p class="text-sm text-slate-500">Noch keine Produkte. Ohne Routing entstehen an dieser Station keine Arbeitsaufträge.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center ring-1 ring-slate-100">
                    <h3 class="font-black">Noch keine Zubereitungsstation</h3>
                    <p class="mt-2 text-sm text-slate-500">Lege links zuerst Küche, Bar oder eine andere Station an.</p>
                </div>
            @endforelse
        </div>
    </div>
</main>
