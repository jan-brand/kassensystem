@php
    use App\Modules\CashRegister\Enums\CashMovementType;
    use App\Modules\CashRegister\Enums\CashSessionStatus;
    use App\Modules\Identity\Enums\UserRole;
    use App\Support\Money;
@endphp

<div class="min-h-screen">
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-4 px-4 py-3 lg:px-6">
            <div class="flex min-w-0 items-center gap-3">
                @if ($settings?->logo_path)
                    <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Logo" class="h-10 w-10 shrink-0 rounded-xl object-contain ring-1 ring-slate-200">
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-500">
                        {{ $settings?->cafeteria_name ?: config('app.name') }}
                    </p>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold">{{ $register->name }}</h1>
                        @if ($session)
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $session->status === CashSessionStatus::Open ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $session->status === CashSessionStatus::Open ? 'Geöffnet' : 'Abschluss läuft' }}
                            </span>
                        @else
                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700">Geschlossen</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden text-right sm:block">
                    <p class="text-sm font-bold">{{ $user->auditDisplayName() }}</p>
                    <p class="text-xs text-slate-500">{{ $user->username }}</p>
                </div>
                @if (in_array($user->role, [UserRole::Manager, UserRole::Administrator], true))
                    <a
                        href="{{ route('administration.dashboard') }}"
                        wire:navigate
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold hover:bg-slate-50"
                    >
                        Administration
                    </a>
                @endif
                @if ($session?->status === CashSessionStatus::Open)
                    <button
                        type="button"
                        wire:click="openCashMenu"
                        class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                    >
                        Kassenmenü
                    </button>
                @endif
                <button
                    type="button"
                    wire:click="switchUser"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold hover:bg-slate-50"
                >
                    Benutzer wechseln
                </button>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1600px] p-4 lg:p-6">
        @if ($notice)
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-medium text-emerald-900">
                {{ $notice }}
            </div>
        @endif

        @if ($screenError)
            <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-medium text-red-900">
                {{ $screenError }}
            </div>
        @endif

        @if (! $session)
            <section class="mx-auto max-w-xl rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                <p class="text-sm font-bold uppercase tracking-[0.15em] text-slate-500">Kassenstart</p>
                <h2 class="mt-2 text-3xl font-bold">Kasse öffnen</h2>
                <p class="mt-2 text-slate-600">Trage den gezählten Anfangsbestand der Bargeldkasse ein.</p>

                <form wire:submit="openCashSession" class="mt-8 space-y-5">
                    <div>
                        <label for="openingCash" class="mb-2 block text-sm font-bold">Anfangsbestand</label>
                        <div class="relative">
                            <input
                                id="openingCash"
                                type="text"
                                inputmode="decimal"
                                wire:model="openingCash"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-4 pr-14 text-right text-2xl font-bold outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                            >
                            <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-lg font-bold text-slate-500">€</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-2xl bg-slate-950 px-5 py-4 text-lg font-bold text-white hover:bg-slate-800">
                        Kasse öffnen
                    </button>
                </form>
            </section>
        @elseif ($session->status === CashSessionStatus::Closing)
            <section class="mx-auto max-w-5xl space-y-5">
                <div class="rounded-3xl border border-amber-200 bg-amber-50 p-6 sm:p-8">
                    <p class="text-sm font-bold uppercase tracking-[0.15em] text-amber-700">Kassenabschluss</p>
                    <h2 class="mt-2 text-3xl font-black text-amber-950">Bargeld zählen und Kasse schließen</h2>
                    <p class="mt-2 max-w-3xl text-amber-900">
                        Neue Verkäufe, Einlagen und Entnahmen sind jetzt gesperrt. Der Abschluss kann abgebrochen werden, solange die Kasse noch nicht geschlossen wurde.
                    </p>
                </div>

                @if ($cashSummary)
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Startbestand</p>
                            <p class="mt-2 text-xl font-black">{{ Money::format($cashSummary['opening_cash_cents'], $currency) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Barumsatz</p>
                            <p class="mt-2 text-xl font-black text-emerald-700">+ {{ Money::format($cashSummary['cash_sales_cents'], $currency) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Einlagen</p>
                            <p class="mt-2 text-xl font-black text-emerald-700">+ {{ Money::format($cashSummary['deposits_cents'], $currency) }}</p>
                        </div>
                        <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Entnahmen</p>
                            <p class="mt-2 text-xl font-black text-red-700">− {{ Money::format($cashSummary['withdrawals_cents'], $currency) }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-950 p-4 text-white shadow-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-300">Sollbestand</p>
                            <p class="mt-2 text-xl font-black">{{ Money::format($cashSummary['expected_cash_cents'], $currency) }}</p>
                        </div>
                    </div>
                @endif

                <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                    <form wire:submit="closeCashSession" class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                        <div>
                            <label for="closingCountedCash" class="block text-sm font-bold">Gezählter Bargeldbestand</label>
                            <div class="relative mt-2">
                                <input
                                    id="closingCountedCash"
                                    type="text"
                                    inputmode="decimal"
                                    wire:model.live.debounce.250ms="closingCountedCash"
                                    placeholder="0,00"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-4 pr-14 text-right text-3xl font-black outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                                >
                                <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-xl font-bold text-slate-500">€</span>
                            </div>
                        </div>

                        @if ($closingDifferenceCents !== null)
                            <div class="mt-4 rounded-2xl p-4 {{ $closingDifferenceCents === 0 ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-950' }}">
                                <div class="flex items-center justify-between gap-4">
                                    <span class="font-bold">Differenz</span>
                                    <span class="text-2xl font-black">{{ Money::format($closingDifferenceCents, $currency) }}</span>
                                </div>
                                @if ($closingDifferenceCents !== 0)
                                    <p class="mt-1 text-sm">Bei einer Differenz ist ein Kommentar verpflichtend.</p>
                                @endif
                            </div>
                        @endif

                        <div class="mt-5">
                            <label for="closingComment" class="block text-sm font-bold">Abschlusskommentar</label>
                            <textarea
                                id="closingComment"
                                wire:model="closingComment"
                                rows="3"
                                placeholder="Bei Differenzen bitte Ursache oder Hinweis dokumentieren …"
                                class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                            ></textarea>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                wire:click="cancelCashClosing"
                                class="rounded-2xl border border-slate-300 bg-white px-5 py-4 text-base font-black hover:bg-slate-50"
                            >
                                Abschluss abbrechen
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-slate-950 px-5 py-4 text-base font-black text-white hover:bg-slate-800"
                            >
                                Kasse schließen
                            </button>
                        </div>
                    </form>

                    <aside class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        <h3 class="font-black">Letzte Kassenbewegungen</h3>
                        <div class="mt-4 divide-y divide-slate-100">
                            @forelse ($recentMovements as $movement)
                                <div class="py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-sm font-bold {{ $movement->type === CashMovementType::Deposit ? 'text-emerald-700' : 'text-red-700' }}">
                                            {{ $movement->type === CashMovementType::Deposit ? 'Einlage' : 'Entnahme' }}
                                        </span>
                                        <span class="font-black">
                                            {{ $movement->type === CashMovementType::Deposit ? '+' : '−' }} {{ Money::format($movement->amount_cents, $currency) }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-600">{{ $movement->reason }}</p>
                                </div>
                            @empty
                                <p class="py-5 text-sm text-slate-500">Keine Kassenbewegungen in dieser Schicht.</p>
                            @endforelse
                        </div>
                    </aside>
                </div>
            </section>
        @else
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_430px]">
                <section class="min-w-0 space-y-4">
                    <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                        <div class="flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                wire:click="selectCategory(null)"
                                class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-bold {{ $selectedCategoryId === null ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-800' }}"
                            >
                                Alle
                            </button>
                            @foreach ($catalog as $category)
                                <button
                                    type="button"
                                    wire:key="category-{{ $category->id }}"
                                    wire:click="selectCategory({{ $category->id }})"
                                    class="shrink-0 rounded-xl px-4 py-2.5 text-sm font-bold {{ $selectedCategoryId === $category->id ? 'bg-slate-950 text-white' : 'bg-slate-100 text-slate-800' }}"
                                >
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                        <input
                            type="search"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Produkt suchen …"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-lg outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                        >
                    </div>

                    @if ($foreignSale)
                        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950">
                            <h2 class="font-bold">Offener Warenkorb eines anderen Benutzers</h2>
                            <p class="mt-1 text-sm">Dieser Warenkorb muss zuerst vom zugehörigen Benutzer abgeschlossen oder verworfen werden.</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-5">
                        @forelse ($products as $product)
                            <button
                                type="button"
                                wire:key="product-{{ $product->id }}"
                                wire:click="addProduct({{ $product->id }})"
                                @disabled($foreignSale)
                                class="flex min-h-32 flex-col justify-between rounded-2xl bg-white p-4 text-left shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <span class="font-bold leading-tight">{{ ($settings?->pos_show_short_names ?? true) ? $product->short_name : $product->name }}</span>
                                <span class="mt-4 text-xl font-black">{{ Money::format($product->price_cents, $currency) }}</span>
                            </button>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                                Keine aktiven Produkte gefunden.
                            </div>
                        @endforelse
                    </div>
                </section>

                <aside class="xl:sticky xl:top-24 xl:self-start">
                    <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <h2 class="text-xl font-bold">Warenkorb</h2>
                                @if ($sale && ! $foreignSale)
                                    <button type="button" wire:click="discardSale" class="text-sm font-bold text-red-700 hover:text-red-900">Verwerfen</button>
                                @endif
                            </div>
                        </div>

                        <div class="max-h-[46vh] divide-y divide-slate-100 overflow-y-auto">
                            @if (! $sale || $sale->items->isEmpty())
                                <div class="p-8 text-center text-slate-500">Noch keine Produkte ausgewählt.</div>
                            @else
                                @foreach ($sale->items as $item)
                                    <div wire:key="cart-item-{{ $item->id }}" class="flex items-center gap-3 px-5 py-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-bold">{{ $item->product_name }}</p>
                                            <p class="text-sm text-slate-500">{{ Money::format($item->unit_price_cents, $currency) }} je Stück</p>
                                        </div>
                                        <div class="flex items-center rounded-xl bg-slate-100 p-1">
                                            <button type="button" wire:click="decreaseItem({{ $item->id }})" class="h-9 w-9 rounded-lg text-xl font-black hover:bg-white">−</button>
                                            <span class="w-9 text-center font-black">{{ $item->quantity }}</span>
                                            <button type="button" wire:click="increaseItem({{ $item->id }})" class="h-9 w-9 rounded-lg text-xl font-black hover:bg-white">+</button>
                                        </div>
                                        <p class="w-24 text-right font-black">{{ Money::format($item->total_cents, $currency) }}</p>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="border-t border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-end justify-between gap-4">
                                <span class="font-bold text-slate-600">Gesamt</span>
                                <span class="text-4xl font-black tracking-tight">{{ Money::format($sale?->total_cents ?? 0, $currency) }}</span>
                            </div>

                            <button
                                type="button"
                                wire:click="showPayment"
                                @disabled(! $sale || $sale->items->isEmpty() || $foreignSale)
                                class="mt-5 w-full rounded-2xl bg-emerald-600 px-5 py-4 text-xl font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                Bezahlen
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </main>

    @if ($cashMenuOpen && $session?->status === CashSessionStatus::Open)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 sm:items-center sm:p-4">
            <div class="max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.15em] text-slate-500">Bargeld</p>
                        <h2 class="mt-1 text-2xl font-black">Kassenmenü</h2>
                    </div>
                    <button type="button" wire:click="closeCashMenu" class="rounded-xl bg-slate-100 px-3 py-2 font-bold">Schließen</button>
                </div>

                @if ($cashMovementMode === null)
                    @if ($cashSummary)
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</p>
                                <p class="mt-1 font-black">{{ Money::format($cashSummary['opening_cash_cents'], $currency) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Barumsatz</p>
                                <p class="mt-1 font-black">{{ Money::format($cashSummary['cash_sales_cents'], $currency) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Einlagen − Entnahmen</p>
                                <p class="mt-1 font-black">{{ Money::format($cashSummary['deposits_cents'] - $cashSummary['withdrawals_cents'], $currency) }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-950 p-4 text-white">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-300">Sollbestand</p>
                                <p class="mt-1 font-black">{{ Money::format($cashSummary['expected_cash_cents'], $currency) }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <button type="button" wire:click="prepareCashMovement('deposit')" class="rounded-2xl bg-emerald-50 px-4 py-5 text-left ring-1 ring-emerald-200 hover:bg-emerald-100">
                            <span class="block text-lg font-black text-emerald-900">+ Einlage</span>
                            <span class="mt-1 block text-sm text-emerald-800">Zusätzliches Bargeld in die Kasse legen.</span>
                        </button>
                        <button type="button" wire:click="prepareCashMovement('withdrawal')" class="rounded-2xl bg-red-50 px-4 py-5 text-left ring-1 ring-red-200 hover:bg-red-100">
                            <span class="block text-lg font-black text-red-900">− Entnahme</span>
                            <span class="mt-1 block text-sm text-red-800">Bargeld dokumentiert aus der Kasse nehmen.</span>
                        </button>
                        <button type="button" wire:click="startCashClosing" class="rounded-2xl bg-amber-50 px-4 py-5 text-left ring-1 ring-amber-200 hover:bg-amber-100">
                            <span class="block text-lg font-black text-amber-950">Kasse abschließen</span>
                            <span class="mt-1 block text-sm text-amber-900">Verkäufe sperren, zählen und Schicht beenden.</span>
                        </button>
                    </div>

                    <div class="mt-6 border-t border-slate-200 pt-5">
                        <h3 class="font-black">Letzte Kassenbewegungen</h3>
                        <div class="mt-2 divide-y divide-slate-100">
                            @forelse ($recentMovements as $movement)
                                <div class="flex items-start justify-between gap-4 py-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold">{{ $movement->reason }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $movement->user?->auditDisplayName() }} · {{ $movement->created_at?->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <span class="shrink-0 font-black {{ $movement->type === CashMovementType::Deposit ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ $movement->type === CashMovementType::Deposit ? '+' : '−' }} {{ Money::format($movement->amount_cents, $currency) }}
                                    </span>
                                </div>
                            @empty
                                <p class="py-4 text-sm text-slate-500">Noch keine Einlagen oder Entnahmen.</p>
                            @endforelse
                        </div>
                    </div>
                @else
                    <form wire:submit="recordCashMovement" class="mt-6">
                        <div class="rounded-2xl p-4 {{ $cashMovementMode === 'deposit' ? 'bg-emerald-50 text-emerald-950' : 'bg-red-50 text-red-950' }}">
                            <p class="text-sm font-bold uppercase tracking-wide">{{ $cashMovementMode === 'deposit' ? 'Einlage' : 'Entnahme' }}</p>
                            <p class="mt-1 text-sm">Betrag und Grund werden unveränderlich protokolliert.</p>
                        </div>

                        <label for="cashMovementAmount" class="mt-5 block text-sm font-bold">Betrag</label>
                        <div class="relative mt-2">
                            <input
                                id="cashMovementAmount"
                                type="text"
                                inputmode="decimal"
                                wire:model="cashMovementAmount"
                                placeholder="0,00"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-4 pr-14 text-right text-3xl font-black outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                            >
                            <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-xl font-bold text-slate-500">€</span>
                        </div>

                        <label for="cashMovementReason" class="mt-5 block text-sm font-bold">Grund</label>
                        <input
                            id="cashMovementReason"
                            type="text"
                            wire:model="cashMovementReason"
                            placeholder="z. B. zusätzliches Wechselgeld"
                            class="mt-2 w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                        >

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('cashMovementMode', null)" class="rounded-2xl border border-slate-300 px-5 py-4 font-black hover:bg-slate-50">Zurück</button>
                            <button type="submit" class="rounded-2xl bg-slate-950 px-5 py-4 font-black text-white hover:bg-slate-800">Buchen</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($paymentOpen && $sale && ! $foreignSale)
        <div class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/45 p-0 sm:items-center sm:p-4">
            <div class="w-full max-w-lg rounded-t-3xl bg-white p-6 shadow-2xl sm:rounded-3xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.15em] text-slate-500">Barzahlung</p>
                        <h2 class="mt-1 text-3xl font-black">{{ Money::format($sale->total_cents, $currency) }}</h2>
                    </div>
                    <button type="button" wire:click="$set('paymentOpen', false)" class="rounded-xl bg-slate-100 px-3 py-2 font-bold">Schließen</button>
                </div>

                @if ($sale->total_cents > 0)
                    <label for="receivedAmount" class="mt-6 block text-sm font-bold">Gegeben</label>
                    <div class="relative mt-2">
                        <input
                            id="receivedAmount"
                            type="text"
                            inputmode="decimal"
                            wire:model="receivedAmount"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-4 pr-14 text-right text-3xl font-black outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                        >
                        <span class="pointer-events-none absolute right-5 top-1/2 -translate-y-1/2 text-xl font-bold text-slate-500">€</span>
                    </div>

                    <div class="mt-3 grid grid-cols-4 gap-2">
                        <button type="button" wire:click="setReceivedAmount({{ $sale->total_cents }})" class="rounded-xl bg-slate-100 px-2 py-3 text-sm font-bold">Passend</button>
                        @foreach ([500, 1000, 2000] as $quickAmount)
                            <button type="button" wire:click="setReceivedAmount({{ $quickAmount }})" class="rounded-xl bg-slate-100 px-2 py-3 text-sm font-bold">
                                {{ Money::format($quickAmount, $currency) }}
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="mt-6 rounded-2xl bg-emerald-50 p-4 text-emerald-900">
                        Dieser Verkauf ist kostenlos und wird trotzdem vollständig protokolliert.
                    </div>
                @endif

                <button type="button" wire:click="completeSale" class="mt-6 w-full rounded-2xl bg-emerald-600 px-5 py-4 text-xl font-black text-white hover:bg-emerald-700">
                    Verkauf abschließen
                </button>
            </div>
        </div>
    @endif

    @if ($lastSaleNumber)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4">
            <div class="w-full max-w-md rounded-3xl bg-white p-7 text-center shadow-2xl">
                <p class="text-sm font-bold uppercase tracking-[0.15em] text-emerald-700">Verkauf abgeschlossen</p>
                <h2 class="mt-2 text-2xl font-black">{{ $lastSaleNumber }}</h2>
                <p class="mt-5 text-sm font-bold text-slate-500">Gesamt</p>
                <p class="text-3xl font-black">{{ Money::format($lastSaleTotalCents ?? 0, $currency) }}</p>
                <p class="mt-5 text-sm font-bold text-slate-500">Rückgeld</p>
                <p class="text-5xl font-black text-emerald-700">{{ Money::format($lastChangeCents ?? 0, $currency) }}</p>
                <button type="button" wire:click="$set('lastSaleNumber', null)" class="mt-7 w-full rounded-2xl bg-slate-950 px-5 py-4 text-lg font-black text-white">
                    Nächster Verkauf
                </button>
            </div>
        </div>
    @endif
</div>
