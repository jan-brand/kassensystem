<div class="min-h-[100dvh] bg-black text-white" wire:poll.3s="pollRefresh" x-on:preparation-new-work.window="window.playPreparationSound($event.detail.sound)">
    <header class="sticky top-0 z-30 border-b border-white/10 bg-black/95 px-4 py-3 backdrop-blur" style="padding-top: max(.75rem, env(safe-area-inset-top));">
        <div class="mx-auto flex max-w-[1800px] items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-white/50">Zubereitung</p>
                <h1 class="text-2xl font-black">{{ $station?->name ?? 'Station auswählen' }}</h1>
            </div>
            <div class="flex gap-2">
                @if($station)
                    <button wire:click="showStationSelection" class="min-h-12 rounded-xl border border-white/20 px-4 text-sm font-black">Station wechseln</button>
                @endif
                <a href="{{ route('waiter.service') }}" class="flex min-h-12 items-center rounded-xl bg-white px-4 text-sm font-black text-black">Kellner-POS</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1800px] p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
        @if($screenError)<div class="mb-4 rounded-2xl border border-red-400/30 bg-red-400/10 px-4 py-3 font-bold text-red-100">{{ $screenError }}</div>@endif

        @if(! $station)
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($stations as $candidate)
                    <button wire:click="chooseStation('{{ $candidate->code }}')" class="min-h-36 rounded-3xl border border-white/10 bg-white/5 p-5 text-left active:scale-[.99]">
                        <span class="block text-2xl font-black">{{ $candidate->name }}</span>
                        <span class="mt-2 block font-mono text-xs text-white/40">{{ $candidate->code }}</span>
                        <span class="mt-5 block text-sm font-bold text-white/60">Display öffnen →</span>
                    </button>
                @empty
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-10 text-center text-white/60 sm:col-span-2">Noch keine aktive Station konfiguriert.</div>
                @endforelse
            </div>
        @else
            <div class="grid gap-4 xl:grid-cols-2">
                @forelse($orders as $order)
                    @php($allOrderTasks = $allTasksByOrder->get($order->id, collect()))
                    @php($readyCount = $allOrderTasks->where('status', \App\Modules\Preparation\Enums\PreparationTaskStatus::Ready)->count())
                    <section class="rounded-3xl border border-white/10 bg-white/5 p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">{{ $order->area_name_snapshot }}</p>
                                <h2 class="mt-1 text-2xl font-black">{{ $order->table_name_snapshot }}</h2>
                                <p class="mt-1 font-mono text-xs text-white/40">{{ $order->number }}</p>
                            </div>
                            <div class="text-right">
                                <span class="rounded-xl bg-white px-3 py-2 text-sm font-black text-black">{{ $orderStatuses[$order->id] ?? 'Neu' }}</span>
                                <p class="mt-2 text-xs font-bold text-white/50">{{ $readyCount }}/{{ $allOrderTasks->count() }} Teile fertig</p>
                            </div>
                        </div>

                        @if($order->note)<div class="mt-4 rounded-2xl border border-amber-300/20 bg-amber-300/10 p-3 text-sm font-bold text-amber-100">Tischnotiz: {{ $order->note }}</div>@endif

                        <div class="mt-4 space-y-3">
                            @foreach($order->items as $item)
                                @php($ownTasks = $stationTasksByItem->get($item->id, collect()))
                                <div class="rounded-2xl border p-4 {{ $ownTasks->isNotEmpty() ? 'border-cyan-300/40 bg-cyan-300/10' : 'border-white/10 bg-black/20 opacity-60' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-lg font-black">{{ $item->quantity }} × {{ $item->label }}</p>
                                            @foreach($item->components as $component)<p class="mt-1 text-xs text-white/50">{{ $component->menu_group_name }}: {{ $component->product_name }}</p>@endforeach
                                            @foreach($item->options as $option)<p class="mt-1 text-xs text-white/50">{{ $option->option_group_name }}: {{ $option->option_value_name }}</p>@endforeach
                                            @if($item->note)<p class="mt-2 text-sm font-bold text-amber-200">Notiz: {{ $item->note }}</p>@endif
                                        </div>
                                        @if($ownTasks->isEmpty())<span class="rounded-lg bg-white/10 px-2 py-1 text-[11px] font-black uppercase text-white/50">Andere Station</span>@endif
                                    </div>

                                    @if($ownTasks->isNotEmpty())
                                        <div class="mt-3 space-y-2">
                                            @foreach($ownTasks as $task)
                                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-black/30 p-3">
                                                    <div>
                                                        <p class="font-black">{{ $task->component_label_snapshot ?: $task->label_snapshot }}</p>
                                                        <p class="mt-1 text-xs font-bold text-white/50">{{ match($task->status->value) { 'new' => 'Neu', 'in_preparation' => 'In Zubereitung', default => 'Fertig' } }}</p>
                                                    </div>
                                                    @if($task->status === \App\Modules\Preparation\Enums\PreparationTaskStatus::New)
                                                        <button wire:click="startTask({{ $task->id }})" class="min-h-12 rounded-xl bg-amber-300 px-4 font-black text-black">Starten</button>
                                                    @elseif($task->status === \App\Modules\Preparation\Enums\PreparationTaskStatus::InPreparation)
                                                        <button wire:click="readyTask({{ $task->id }})" class="min-h-12 rounded-xl bg-emerald-400 px-4 font-black text-black">Fertig</button>
                                                    @else
                                                        <span class="rounded-xl bg-emerald-400/20 px-3 py-2 font-black text-emerald-200">Fertig</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="rounded-3xl border border-white/10 bg-white/5 p-12 text-center text-white/60 xl:col-span-2">
                        <p class="text-xl font-black text-white">Keine offene Arbeit</p>
                        <p class="mt-2 text-sm">Neue Bestellungen für {{ $station->name }} erscheinen automatisch.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </main>
</div>
