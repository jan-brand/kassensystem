<div class="min-h-[100dvh] bg-black text-white" wire:poll.3s>
    <main class="mx-auto max-w-[1400px] space-y-5 p-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-white/50">Kellner · Zubereitung</p>
                <h1 class="mt-1 text-3xl font-black">Fertige Teile</h1>
                <p class="mt-2 text-sm text-white/50">Nur offene Tischvorgänge. V2 kennt bewusst keinen zusätzlichen Status „Serviert“.</p>
            </div>
            <a href="{{ route('preparation.display') }}" class="flex min-h-12 items-center rounded-xl border border-white/20 px-4 text-sm font-black">Stationsdisplays</a>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse($readyByOrder as $tasks)
                @php($order = $tasks->first()->order)
                <section class="rounded-3xl border border-emerald-400/20 bg-emerald-400/10 p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.16em] text-emerald-100/60">{{ $order->area_name_snapshot }}</p>
                            <h2 class="mt-1 text-2xl font-black">{{ $order->table_name_snapshot }}</h2>
                            <p class="mt-1 font-mono text-xs text-white/40">{{ $order->number }}</p>
                        </div>
                        <span class="rounded-xl bg-emerald-400 px-3 py-2 text-sm font-black text-black">{{ $tasks->count() }} fertig</span>
                    </div>
                    <div class="mt-4 divide-y divide-white/10">
                        @foreach($tasks as $task)
                            <div class="flex items-start justify-between gap-3 py-3">
                                <div>
                                    <p class="font-black">{{ $task->quantity }} × {{ $task->component_label_snapshot ?: $task->label_snapshot }}</p>
                                    @if($task->note_snapshot)<p class="mt-1 text-sm font-bold text-amber-200">Notiz: {{ $task->note_snapshot }}</p>@endif
                                </div>
                                <span class="rounded-lg bg-white/10 px-2 py-1 text-xs font-black">{{ $task->station->name }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="rounded-3xl border border-white/10 bg-white/5 p-12 text-center text-white/60 lg:col-span-2">
                    <p class="text-xl font-black text-white">Aktuell nichts fertig</p>
                    <p class="mt-2 text-sm">Fertigmeldungen aus Küche, Bar und weiteren Stationen erscheinen hier automatisch.</p>
                </div>
            @endforelse
        </div>
    </main>
</div>
