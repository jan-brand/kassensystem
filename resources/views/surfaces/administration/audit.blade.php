<main class="admin-page admin-page--dense space-y-6">
    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Administration</p>
                <h2 class="mt-2 text-3xl font-black">Audit-Protokoll</h2>
                <p class="mt-2 max-w-3xl text-sm text-slate-300">
                    Unveränderliche fachliche Ereignisse mit Benutzer-Snapshot, Subject und Änderungsdaten.
                </p>
            </div>
            <div class="rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm text-slate-300">
                Nur Administratoren
            </div>
        </div>
    </section>

    <section class="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
        <form wire:submit="applyFilters" class="space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black">Filter</h3>
                    <p class="text-sm text-slate-500">Alle Felder sind optional.</p>
                </div>
                <button type="button" wire:click="resetFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50">
                    Zurücksetzen
                </button>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <label class="space-y-1 xl:col-span-2">
                    <span class="text-sm font-bold text-slate-700">Event-Key</span>
                    <input wire:model="eventKey" type="text" placeholder="z. B. sale.completed" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('eventKey') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 xl:col-span-2">
                    <span class="text-sm font-bold text-slate-700">Benutzer</span>
                    <input wire:model="actor" type="text" placeholder="Username, Anzeigename oder ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('actor') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Von</span>
                    <input wire:model="dateFrom" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('dateFrom') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Bis</span>
                    <input wire:model="dateTo" type="date" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('dateTo') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 xl:col-span-4">
                    <span class="text-sm font-bold text-slate-700">Subject-Typ</span>
                    <input wire:model="subjectType" type="text" placeholder="z. B. Product, User oder Sale" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('subjectType') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <label class="space-y-1 xl:col-span-1">
                    <span class="text-sm font-bold text-slate-700">Subject-ID</span>
                    <input wire:model="subjectId" type="number" min="1" step="1" placeholder="ID" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                    @error('subjectId') <span class="text-sm text-red-700">{{ $message }}</span> @enderror
                </label>

                <div class="flex items-end xl:col-span-1">
                    <button type="submit" class="w-full rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white hover:bg-slate-800">
                        Anwenden
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black">Ereignisse</h3>
                    <p class="text-sm text-slate-500">Neueste Einträge zuerst · {{ $events->total() }} Treffer</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 sm:px-6">Zeitpunkt</th>
                        <th class="px-5 py-3">Ereignis</th>
                        <th class="px-5 py-3">Benutzer</th>
                        <th class="px-5 py-3">Subject</th>
                        <th class="px-5 py-3 text-right sm:px-6">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($events as $event)
                        <tr wire:key="audit-{{ $event->id }}" class="align-top">
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-700 sm:px-6">
                                {{ $event->created_at?->format('d.m.Y H:i:s') }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs font-bold text-slate-800">{{ $event->event_key }}</span>
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($event->actor_username || $event->actor_display_name)
                                    <div class="font-bold">{{ $event->actor_display_name ?: $event->actor_username }}</div>
                                    <div class="text-xs text-slate-500">
                                        {{ $event->actor_username }}@if($event->actor_user_id) · #{{ $event->actor_user_id }}@endif
                                    </div>
                                @else
                                    <span class="text-slate-500">System</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                @if($event->subject_type)
                                    <div class="font-bold" title="{{ $event->subject_type }}">{{ class_basename($event->subject_type) }}</div>
                                    @if($event->subject_id)
                                        <div class="text-xs text-slate-500">#{{ $event->subject_id }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right sm:px-6">
                                <button type="button" wire:click="showEvent({{ $event->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-black hover:bg-slate-50">
                                    Öffnen
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500">Keine Audit-Ereignisse für diese Filter gefunden.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">
                {{ $events->links() }}
            </div>
        @endif
    </section>

    @if($selectedEvent)
        <div class="admin-overlay fixed inset-0 z-50 overflow-y-auto p-4 sm:p-8" wire:click.self="closeEvent">
            <section class="admin-dialog mx-auto max-w-5xl overflow-hidden rounded-3xl">
                <header class="admin-dialog__header flex items-start justify-between gap-4 border-b px-5 py-5 sm:px-7">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-500">Audit #{{ $selectedEvent->id }}</p>
                        <h3 class="mt-1 break-all text-xl font-black">{{ $selectedEvent->event_key }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $selectedEvent->created_at?->format('d.m.Y H:i:s') }}</p>
                    </div>
                    <button type="button" wire:click="closeEvent" class="admin-dialog__close px-3 py-2 text-sm">Schließen</button>
                </header>

                <div class="space-y-6 p-5 sm:p-7">
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Benutzer</div>
                            <div class="mt-2 font-black">{{ $selectedEvent->actor_display_name ?: ($selectedEvent->actor_username ?: 'System') }}</div>
                            <div class="mt-1 break-all text-xs text-slate-500">
                                {{ $selectedEvent->actor_username ?: '—' }}@if($selectedEvent->actor_user_id) · #{{ $selectedEvent->actor_user_id }}@endif
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">Subject</div>
                            <div class="mt-2 break-all font-black">{{ $selectedEvent->subject_type ? class_basename($selectedEvent->subject_type) : '—' }}</div>
                            <div class="mt-1 break-all text-xs text-slate-500">{{ $selectedEvent->subject_type ?: '—' }}@if($selectedEvent->subject_id) · #{{ $selectedEvent->subject_id }}@endif</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">IP-Adresse</div>
                            <div class="mt-2 break-all font-mono text-sm">{{ $selectedEvent->ip_address ?: '—' }}</div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="text-xs font-bold uppercase tracking-wide text-slate-500">User-Agent</div>
                            <div class="mt-2 break-all text-xs text-slate-700">{{ $selectedEvent->user_agent ?: '—' }}</div>
                        </div>
                    </div>

                    @php($jsonOptions = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    <div class="grid gap-4 xl:grid-cols-3">
                        @foreach(['before' => 'Vorher', 'after' => 'Nachher', 'metadata' => 'Metadaten'] as $field => $label)
                            <div class="admin-dialog__data-card min-w-0 rounded-2xl border">
                                <div class="border-b px-4 py-3 text-sm font-black">{{ $label }}</div>
                                <pre class="max-h-80 overflow-auto whitespace-pre-wrap break-words p-4 text-xs leading-5 text-slate-700">{{ $selectedEvent->{$field} === null ? '—' : json_encode($selectedEvent->{$field}, $jsonOptions) }}</pre>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    @endif
</main>
