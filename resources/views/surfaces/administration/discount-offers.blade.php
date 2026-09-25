@php
    use App\Modules\Sales\Enums\DiscountType;
    use App\Support\Money;

    $weekdayLabels = [
        '1' => 'Mo',
        '2' => 'Di',
        '3' => 'Mi',
        '4' => 'Do',
        '5' => 'Fr',
        '6' => 'Sa',
        '7' => 'So',
    ];
@endphp

<main class="admin-page admin-page--dense space-y-6">
    <section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.24em] text-slate-400">Verkauf</p>
        <h2 class="mt-2 text-3xl font-black">Tagesangebote</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">
            Pro Produkt kann ein vordefiniertes Angebot aktiv sein. Datum, Wochentage und tägliches Zeitfenster sind optional und werden gemeinsam ausgewertet.
        </p>
    </section>

    @if ($notice)
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-900">{{ $notice }}</div>
    @endif

    @if ($screenError)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 font-bold text-red-900">{{ $screenError }}</div>
    @endif

    <section class="rounded-3xl bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-black">{{ $editingOfferId ? 'Angebot bearbeiten' : 'Angebot anlegen' }}</h3>
                <p class="text-sm text-slate-500">Rabattwerte werden in Cent bzw. Prozent ohne Fließkommazahlen gespeichert.</p>
            </div>
            @if ($editingOfferId)
                <button type="button" wire:click="cancelEdit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold hover:bg-slate-50">
                    Abbrechen
                </button>
            @endif
        </div>

        <form wire:submit="save" class="mt-5 space-y-5">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Produkt</span>
                    <select wire:model="productId" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        <option value="">Bitte wählen</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">{{ $product->category->name }} · {{ $product->name }} · {{ Money::format($product->price_cents, $currency) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Angebotsname</span>
                    <input wire:model="name" type="text" maxlength="160" placeholder="z. B. Happy Hour" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Rabattart</span>
                    <select wire:model.live="discountType" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">
                        {{ $discountType === DiscountType::Percentage->value ? 'Prozent' : 'Betrag / Endpreis' }}
                    </span>
                    <input wire:model="discountValue" type="text" inputmode="decimal" class="w-full rounded-xl border border-slate-300 px-3 py-2.5" placeholder="{{ $discountType === DiscountType::Percentage->value ? '10' : '1,00' }}">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Start (optional)</span>
                    <input wire:model="startsAt" type="datetime-local" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Ende (optional)</span>
                    <input wire:model="endsAt" type="datetime-local" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Täglicher Start (optional)</span>
                    <input wire:model="dailyStartTime" type="time" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-bold text-slate-700">Tägliches Ende (optional)</span>
                    <input wire:model="dailyEndTime" type="time" class="w-full rounded-xl border border-slate-300 px-3 py-2.5">
                </label>
            </div>

            <fieldset>
                <legend class="text-sm font-bold text-slate-700">Wochentage (leer = täglich)</legend>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($weekdayLabels as $value => $label)
                        <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold">
                            <input wire:model="weekdays" type="checkbox" value="{{ $value }}">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4">
                <input wire:model="active" type="checkbox" class="h-5 w-5">
                <span>
                    <span class="block font-black">Aktiv</span>
                    <span class="block text-sm text-slate-500">Das Angebot muss zusätzlich zu allen Zeitregeln aktiv sein.</span>
                </span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-black text-white hover:bg-slate-800">
                    Angebot speichern
                </button>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-3xl bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h3 class="text-lg font-black">Vorbereitete Angebote</h3>
            <p class="text-sm text-slate-500">{{ $offers->count() }} Angebot(e)</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse ($offers as $offer)
                <article class="grid gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[1fr_auto]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-black">{{ $offer->name }}</h4>
                            <span class="rounded-lg px-2 py-1 text-xs font-black {{ $offer->active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                {{ $offer->active ? 'Aktiv' : 'Inaktiv' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm font-bold text-slate-700">{{ $offer->product->category->name }} · {{ $offer->product->name }}</p>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $offer->type->label() }}:
                            @if ($offer->type === DiscountType::Percentage)
                                {{ intdiv($offer->value, 100) }},{{ str_pad((string) ($offer->value % 100), 2, '0', STR_PAD_LEFT) }} %
                            @else
                                {{ Money::format($offer->value, $currency) }}
                            @endif
                        </p>
                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            @if ($offer->starts_at || $offer->ends_at)
                                Zeitraum: {{ $offer->starts_at?->format('d.m.Y H:i') ?? 'offen' }} – {{ $offer->ends_at?->format('d.m.Y H:i') ?? 'offen' }}<br>
                            @endif
                            @if ($offer->weekdays)
                                Wochentage: {{ collect($offer->weekdays)->map(fn ($day) => $weekdayLabels[(string) $day] ?? $day)->join(', ') }}<br>
                            @endif
                            @if ($offer->daily_start_time && $offer->daily_end_time)
                                Uhrzeit: {{ $offer->daily_start_time }} – {{ $offer->daily_end_time }}
                            @endif
                        </p>
                    </div>

                    <div class="flex items-start gap-2">
                        <button type="button" wire:click="edit({{ $offer->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-black hover:bg-slate-50">
                            Bearbeiten
                        </button>
                        <button type="button" wire:click="toggle({{ $offer->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-black hover:bg-slate-50">
                            {{ $offer->active ? 'Deaktivieren' : 'Aktivieren' }}
                        </button>
                    </div>
                </article>
            @empty
                <p class="px-5 py-10 text-center text-slate-500">Noch keine Tagesangebote angelegt.</p>
            @endforelse
        </div>
    </section>
</main>
