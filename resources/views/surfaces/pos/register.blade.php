@php
    use App\Modules\CashRegister\Enums\CashMovementType;
    use App\Modules\CashRegister\Enums\CashSessionStatus;
    use App\Support\Money;
@endphp

<div class="pos-terminal min-h-[100dvh]">
    <header class="header-pos sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur" style="padding-top: env(safe-area-inset-top);">
        <div class="mx-auto flex max-w-[1600px] items-center justify-between gap-2 px-3 py-2.5 sm:gap-4 sm:px-4 sm:py-3 lg:px-6">
            <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                @if ($settings?->logo_path)
                    <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Logo" class="h-9 w-9 shrink-0 rounded-xl object-contain ring-1 ring-slate-200 sm:h-10 sm:w-10">
                @endif
                <div class="min-w-0">
                    <p class="pos-brand-name truncate text-xs font-semibold text-slate-500 sm:text-sm">
                        {{ $settings?->cafeteria_name ?: config('app.name') }}
                    </p>
                    <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                        <h1 class="pos-register-name truncate text-base font-black sm:text-xl">{{ $register->name }}</h1>
                        @if ($session)
                            <span class="pos-session-badge hidden rounded-full px-2.5 py-1 text-xs font-bold sm:inline-flex {{ $session->status === CashSessionStatus::Open ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $session->status === CashSessionStatus::Open ? 'Geöffnet' : 'Abschluss läuft' }}
                            </span>
                        @else
                            <span class="hidden rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 sm:inline-flex">Geschlossen</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                <div class="hidden text-right lg:block">
                    <p class="pos-user-name text-sm font-bold">{{ $user->auditDisplayName() }}</p>
                    <p class="pos-user-id text-xs text-slate-500">{{ $user->username }}</p>
                </div>
                @can('administration.access')
                    <a
                        href="{{ route('administration.dashboard') }}"
                        wire:navigate
                        class="pos-header-action hidden rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-bold hover:bg-slate-50 sm:inline-flex"
                    >
                        Admin
                    </a>
                @endcan
                @if ($session?->status === CashSessionStatus::Open)
                    <button
                        type="button"
                        wire:click="openCashMenu"
                        wire:loading.attr="disabled"
                        class="pos-header-action pos-header-action--cash touch-manipulation rounded-xl bg-slate-950 px-3 py-2.5 text-sm font-bold text-white hover:bg-slate-800 disabled:opacity-50 sm:px-4"
                    >
                        Kasse
                    </button>
                @endif
                <button
                    type="button"
                    wire:click="switchUser"
                    wire:loading.attr="disabled"
                    class="pos-header-action touch-manipulation rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-bold hover:bg-slate-50 disabled:opacity-50"
                >
                    <span class="sm:hidden">Wechsel</span>
                    <span class="hidden sm:inline">Benutzer wechseln</span>
                </button>
            </div>
        </div>
    </header>

    <main class="pos-terminal__main mx-auto max-w-[1600px] px-3 py-3 pb-28 sm:px-4 sm:py-4 lg:p-6 xl:pb-6">
        @if ($notice)
            <div class="pos-feedback pos-feedback--success" role="status" aria-live="polite">
                {{ $notice }}
            </div>
        @endif

        @if ($screenError)
            <div class="pos-feedback pos-feedback--danger" role="alert">
                {{ $screenError }}
            </div>
        @endif

        @if (! $session)
            <section class="pos-start-card">
                <p class="pos-eyebrow">Kassenstart</p>
                <h2 class="pos-start-title">Kasse öffnen</h2>
                <p class="pos-start-copy">Trage den gezählten Anfangsbestand der Bargeldkasse ein.</p>

                <form wire:submit="openCashSession" class="pos-start-form">
                    <div>
                        <label for="openingCash" class="pos-field-label">Anfangsbestand</label>
                        <div class="pos-money-input">
                            <input
                                id="openingCash"
                                type="text"
                                inputmode="decimal"
                                wire:model="openingCash"
                                placeholder="0,00"
                                autocomplete="off"
                                class="pos-money-input__control"
                            >
                            <span class="pos-money-input__currency">€</span>
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" class="pos-primary-action touch-manipulation disabled:opacity-50">
                        <span wire:loading.remove wire:target="openCashSession">Kasse öffnen</span>
                        <span wire:loading wire:target="openCashSession">Wird geöffnet …</span>
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
            <div class="pos-workspace grid gap-4 xl:grid-cols-[minmax(0,1fr)_430px] xl:gap-5">
                <section class="pos-catalog min-w-0 space-y-3 sm:space-y-4">
                    <div class="pos-category-bar -mx-3 border-y border-slate-200 bg-white px-3 py-2.5 shadow-sm sm:mx-0 sm:rounded-2xl sm:border-0 sm:p-3 sm:ring-1 sm:ring-slate-200">
                        <div class="pos-category-scroll flex gap-2 overflow-x-auto pb-1">
                            <button
                                type="button"
                                wire:click="selectCategory(null)"
                                class="pos-category-button shrink-0 touch-manipulation rounded-xl px-4 py-3 text-sm font-bold {{ $selectedCategoryId === null ? 'is-active' : '' }}"
                            >
                                Alle
                            </button>
                            @foreach ($catalog as $category)
                                <button
                                    type="button"
                                    wire:key="category-{{ $category->id }}"
                                    wire:click="selectCategory({{ $category->id }})"
                                    class="pos-category-button shrink-0 touch-manipulation rounded-xl px-4 py-3 text-sm font-bold {{ $selectedCategoryId === $category->id ? 'is-active' : '' }}"
                                >
                                    {{ $category->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="pos-search rounded-2xl bg-white p-2.5 shadow-sm ring-1 ring-slate-200 sm:p-3">
                        <input
                            type="search"
                            wire:model.live.debounce.250ms="search"
                            placeholder="Produkt suchen …"
                            enterkeyhint="search"
                            autocomplete="off"
                            class="pos-search__input w-full rounded-xl border border-slate-300 px-4 py-3 text-base outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10 sm:text-lg"
                        >
                    </div>

                    @if ($foreignSale)
                        <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-amber-950 sm:p-5">
                            <h2 class="font-bold">Offener Warenkorb eines anderen Benutzers</h2>
                            <p class="mt-1 text-sm">Dieser Warenkorb muss zuerst vom zugehörigen Benutzer abgeschlossen oder verworfen werden.</p>
                        </div>
                    @endif

                    <div class="pos-product-grid grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-4 2xl:grid-cols-5">
                        @forelse ($products as $product)
                            <button
                                type="button"
                                wire:key="product-{{ $product->id }}"
                                wire:click="addProduct({{ $product->id }})"
                                wire:loading.attr="disabled"
                                @disabled($foreignSale)
                                class="pos-product-tile flex min-h-28 touch-manipulation select-none flex-col justify-between rounded-2xl bg-white p-3 text-left shadow-sm ring-1 ring-slate-200 transition active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 sm:min-h-32 sm:p-4"
                            >
                                <span class="pos-product-name font-bold leading-tight">{{ ($settings?->pos_show_short_names ?? true) ? $product->short_name : $product->name }}</span>
                                <span class="pos-product-price mt-3 text-lg font-black sm:mt-4 sm:text-xl">{{ Money::format($product->price_cents, $currency) }}</span>
                            </button>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 sm:p-10">
                                Keine aktiven Produkte gefunden.
                            </div>
                        @endforelse
                    </div>
                </section>

                <aside class="pos-cart hidden xl:sticky xl:top-24 xl:block xl:self-start">
                    <div class="pos-cart__panel overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-200">
                        <div class="pos-cart__header border-b border-slate-200 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <h2 class="pos-cart__title text-xl font-bold">Warenkorb</h2>
                                @if ($sale && ! $foreignSale)
                                    <button type="button" wire:click="discardSale" wire:loading.attr="disabled" class="pos-cart__discard text-sm font-bold text-red-700 hover:text-red-900 disabled:opacity-50">Verwerfen</button>
                                @endif
                            </div>
                        </div>

                        <div class="pos-cart__body max-h-[46vh] divide-y divide-slate-100 overflow-y-auto">
                            @if (! $sale || $sale->items->isEmpty())
                                <div class="pos-cart__empty text-center">
                                    <svg
                                        class="pos-cart__empty-icon"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.6"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6.1" />
                                        <circle cx="9" cy="20" r="1" />
                                        <circle cx="18" cy="20" r="1" />
                                    </svg>
                                    <p class="pos-cart__empty-text">Noch keine Produkte ausgewählt.</p>
                                </div>
                            @else
                                @foreach ($sale->items as $item)
                                    <div wire:key="cart-item-desktop-{{ $item->id }}" class="pos-cart__row flex items-center gap-3 px-5 py-4">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-bold">{{ $item->product_name }}</p>
                                            <p class="pos-cart__unit text-sm text-slate-500">{{ Money::format($item->unit_price_cents, $currency) }} je Stück</p>
                                        </div>
                                        <div class="pos-quantity flex items-center rounded-xl bg-slate-100 p-1">
                                            <button type="button" wire:click="decreaseItem({{ $item->id }})" wire:loading.attr="disabled" class="h-10 w-10 touch-manipulation rounded-lg text-xl font-black hover:bg-white disabled:opacity-50">−</button>
                                            <span class="w-9 text-center font-black">{{ $item->quantity }}</span>
                                            <button type="button" wire:click="increaseItem({{ $item->id }})" wire:loading.attr="disabled" class="h-10 w-10 touch-manipulation rounded-lg text-xl font-black hover:bg-white disabled:opacity-50">+</button>
                                        </div>
                                        <p class="w-24 text-right font-black">{{ Money::format($item->total_cents, $currency) }}</p>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        <div class="pos-cart__footer border-t border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-end justify-between gap-4">
                                <span class="pos-cart__total-label font-bold text-slate-600">Gesamt</span>
                                <span class="pos-cart__total text-4xl font-black tracking-tight">{{ Money::format($sale?->total_cents ?? 0, $currency) }}</span>
                            </div>

                            <button
                                type="button"
                                wire:click="showPayment"
                                wire:loading.attr="disabled"
                                @disabled(! $sale || $sale->items->isEmpty() || $foreignSale)
                                class="pos-pay-button mt-5 w-full touch-manipulation rounded-2xl bg-emerald-600 px-5 py-4 text-xl font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                Bezahlen
                            </button>
                        </div>
                    </div>
                </aside>
            </div>

            <div
                data-pos-mobile-bar
                class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-3 pt-2.5 shadow-[0_-8px_30px_rgba(15,23,42,0.12)] backdrop-blur xl:hidden"
                style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));"
            >
                <div class="mx-auto flex max-w-3xl items-center gap-2">
                    <button
                        type="button"
                        wire:click="openMobileCart"
                        wire:loading.attr="disabled"
                        class="min-w-0 flex-1 touch-manipulation rounded-2xl border border-slate-300 bg-white px-4 py-3 text-left disabled:opacity-50"
                    >
                        <span class="block text-xs font-bold uppercase tracking-wide text-slate-500">{{ $cartItemCount }} Artikel</span>
                        <span class="block truncate text-xl font-black">{{ Money::format($sale?->total_cents ?? 0, $currency) }}</span>
                    </button>
                    <button
                        type="button"
                        wire:click="showPayment"
                        wire:loading.attr="disabled"
                        @disabled(! $sale || $sale->items->isEmpty() || $foreignSale)
                        class="touch-manipulation rounded-2xl bg-emerald-600 px-5 py-4 text-base font-black text-white disabled:cursor-not-allowed disabled:bg-slate-300 sm:px-7"
                    >
                        Bezahlen
                    </button>
                </div>
            </div>
        @endif
    </main>

    @if ($mobileCartOpen && $session?->status === CashSessionStatus::Open)
        <div data-pos-mobile-cart class="fixed inset-0 z-50 flex items-end xl:hidden" wire:click.self="closeMobileCart">
            <section class="max-h-[88dvh] w-full overflow-hidden rounded-t-3xl">
                <header class="flex items-center justify-between gap-3 border-b px-4 py-4">
                    <div>
                        <p class="pos-mobile-cart__meta text-xs font-bold uppercase tracking-wide">{{ $cartItemCount }} Artikel</p>
                        <h2 class="text-xl font-black">Warenkorb</h2>
                    </div>
                    <button type="button" wire:click="closeMobileCart" class="pos-dialog-close touch-manipulation">Schließen</button>
                </header>

                <div class="pos-mobile-cart__body max-h-[52dvh] overflow-y-auto overscroll-contain">
                    @if (! $sale || $sale->items->isEmpty())
                        <div class="pos-cart__empty text-center">
                            <svg
                                class="pos-cart__empty-icon"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.6"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M3 4h2l2.2 10.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H6.1" />
                                <circle cx="9" cy="20" r="1" />
                                <circle cx="18" cy="20" r="1" />
                            </svg>
                            <p class="pos-cart__empty-text">Noch keine Produkte ausgewählt.</p>
                        </div>
                    @else
                        @foreach ($sale->items as $item)
                            <div wire:key="cart-item-mobile-{{ $item->id }}" class="pos-mobile-cart__row flex items-center gap-3 border-t px-4 py-4 first:border-t-0">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-bold">{{ $item->product_name }}</p>
                                    <p class="pos-cart__unit text-sm">{{ Money::format($item->unit_price_cents, $currency) }} je Stück</p>
                                </div>
                                <div class="pos-quantity flex shrink-0 items-center rounded-xl p-1">
                                    <button type="button" wire:click="decreaseItem({{ $item->id }})" wire:loading.attr="disabled" class="h-11 w-11 touch-manipulation rounded-lg text-xl font-black disabled:opacity-50">−</button>
                                    <span class="w-9 text-center font-black">{{ $item->quantity }}</span>
                                    <button type="button" wire:click="increaseItem({{ $item->id }})" wire:loading.attr="disabled" class="h-11 w-11 touch-manipulation rounded-lg text-xl font-black disabled:opacity-50">+</button>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <footer class="border-t px-4 pt-4" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                    <div class="flex items-end justify-between gap-4">
                        <span class="pos-cart__total-label font-bold">Gesamt</span>
                        <span class="text-3xl font-black">{{ Money::format($sale?->total_cents ?? 0, $currency) }}</span>
                    </div>
                    <div class="mt-4 grid grid-cols-[auto_1fr] gap-2">
                        @if ($sale && ! $foreignSale)
                            <button type="button" wire:click="discardSale" wire:loading.attr="disabled" class="pos-cart__discard touch-manipulation px-4 py-4 text-sm font-black disabled:opacity-50">Verwerfen</button>
                        @endif
                        <button
                            type="button"
                            wire:click="showPayment"
                            wire:loading.attr="disabled"
                            @disabled(! $sale || $sale->items->isEmpty() || $foreignSale)
                            class="pos-pay-button touch-manipulation px-5 py-4 text-lg font-black disabled:cursor-not-allowed"
                        >
                            Bezahlen
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    @endif

    @if ($cashMenuOpen && $session?->status === CashSessionStatus::Open)
        <div class="pos-dialog-backdrop fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
            <div class="pos-dialog-card pos-dialog-card--wide" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom));">
                <div class="pos-dialog-header">
                    <div>
                        <p class="pos-eyebrow">Bargeld</p>
                        <h2 class="pos-dialog-title">Kassenmenü</h2>
                    </div>
                    <button type="button" wire:click="closeCashMenu" class="pos-dialog-close">Schließen</button>
                </div>

                @if ($cashMovementMode === null)
                    @if ($cashSummary)
                        <div class="pos-cash-summary">
                            <div class="pos-cash-stat">
                                <p class="pos-cash-stat__label">Start</p>
                                <p class="pos-cash-stat__value">{{ Money::format($cashSummary['opening_cash_cents'], $currency) }}</p>
                            </div>
                            <div class="pos-cash-stat">
                                <p class="pos-cash-stat__label">Barumsatz</p>
                                <p class="pos-cash-stat__value">{{ Money::format($cashSummary['cash_sales_cents'], $currency) }}</p>
                            </div>
                            <div class="pos-cash-stat">
                                <p class="pos-cash-stat__label">Einlagen − Entnahmen</p>
                                <p class="pos-cash-stat__value">{{ Money::format($cashSummary['deposits_cents'] - $cashSummary['withdrawals_cents'], $currency) }}</p>
                            </div>
                            <div class="pos-cash-stat pos-cash-stat--expected">
                                <p class="pos-cash-stat__label">Sollbestand</p>
                                <p class="pos-cash-stat__value">{{ Money::format($cashSummary['expected_cash_cents'], $currency) }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="pos-cash-actions">
                        <button type="button" wire:click="prepareCashMovement('deposit')" class="pos-cash-action pos-cash-action--deposit">
                            <span class="pos-cash-action__title">+ Einlage</span>
                            <span class="pos-cash-action__copy">Zusätzliches Bargeld in die Kasse legen.</span>
                        </button>
                        <button type="button" wire:click="prepareCashMovement('withdrawal')" class="pos-cash-action pos-cash-action--withdrawal">
                            <span class="pos-cash-action__title">− Entnahme</span>
                            <span class="pos-cash-action__copy">Bargeld dokumentiert aus der Kasse nehmen.</span>
                        </button>
                        <button type="button" wire:click="startCashClosing" class="pos-cash-action pos-cash-action--closing">
                            <span class="pos-cash-action__title">Kasse abschließen</span>
                            <span class="pos-cash-action__copy">Verkäufe sperren, zählen und Schicht beenden.</span>
                        </button>
                    </div>

                    <div class="pos-dialog-section">
                        <h3 class="pos-dialog-section__title">Letzte Kassenbewegungen</h3>
                        <div class="pos-movement-list">
                            @forelse ($recentMovements as $movement)
                                <div class="pos-movement-row">
                                    <div class="min-w-0">
                                        <p class="pos-movement-reason">{{ $movement->reason }}</p>
                                        <p class="pos-movement-meta">{{ $movement->user?->auditDisplayName() }} · {{ $movement->created_at?->format('d.m.Y H:i') }}</p>
                                    </div>
                                    <span class="pos-movement-amount {{ $movement->type === CashMovementType::Deposit ? 'pos-movement-amount--deposit' : 'pos-movement-amount--withdrawal' }}">
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
                        <div class="pos-context-note {{ $cashMovementMode === 'deposit' ? 'pos-context-note--deposit' : 'pos-context-note--withdrawal' }}">
                            <p class="pos-context-note__title">{{ $cashMovementMode === 'deposit' ? 'Einlage' : 'Entnahme' }}</p>
                            <p class="pos-context-note__copy">Betrag und Grund werden unveränderlich protokolliert.</p>
                        </div>

                        <label for="cashMovementAmount" class="pos-field-label mt-5">Betrag</label>
                        <div class="pos-money-input">
                            <input
                                id="cashMovementAmount"
                                type="text"
                                inputmode="decimal"
                                wire:model="cashMovementAmount"
                                placeholder="0,00"
                                class="pos-money-input__control"
                            >
                            <span class="pos-money-input__currency">€</span>
                        </div>

                        <label for="cashMovementReason" class="pos-field-label mt-5">Grund</label>
                        <input
                            id="cashMovementReason"
                            type="text"
                            wire:model="cashMovementReason"
                            placeholder="z. B. zusätzliches Wechselgeld"
                            class="pos-text-input"
                        >

                        <div class="mt-6 grid grid-cols-2 gap-3">
                            <button type="button" wire:click="$set('cashMovementMode', null)" class="pos-secondary-action">Zurück</button>
                            <button type="submit" class="pos-primary-action">Buchen</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @endif

    @if ($paymentOpen && $sale && ! $foreignSale)
        <div class="pos-dialog-backdrop fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
            <div class="pos-dialog-card" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom));">
                <div class="pos-dialog-header">
                    <div>
                        <p class="pos-eyebrow">Barzahlung</p>
                        <h2 class="pos-dialog-total">{{ Money::format($sale->total_cents, $currency) }}</h2>
                    </div>
                    <button type="button" wire:click="$set('paymentOpen', false)" class="pos-dialog-close">Schließen</button>
                </div>

                @if ($sale->total_cents > 0)
                    <div class="pos-dialog-field">
                        <label for="receivedAmount" class="pos-field-label">Gegeben</label>
                        <div class="pos-money-input">
                            <input
                                id="receivedAmount"
                                type="text"
                                inputmode="decimal"
                                wire:model="receivedAmount"
                                placeholder="0,00"
                                autocomplete="off"
                                class="pos-money-input__control"
                            >
                            <span class="pos-money-input__currency">€</span>
                        </div>
                    </div>

                    <div class="pos-quick-amounts">
                        <button type="button" wire:click="setReceivedAmount({{ $sale->total_cents }})" class="pos-quick-amount">Passend</button>
                        @foreach ([500, 1000, 2000] as $quickAmount)
                            <button type="button" wire:click="setReceivedAmount({{ $quickAmount }})" class="pos-quick-amount">
                                {{ Money::format($quickAmount, $currency) }}
                            </button>
                        @endforeach
                    </div>
                @else
                    <div class="pos-zero-sale">
                        Dieser Verkauf ist kostenlos und wird trotzdem vollständig protokolliert.
                    </div>
                @endif

                <button type="button" wire:click="completeSale" wire:loading.attr="disabled" class="pos-dialog-action touch-manipulation disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="completeSale">Verkauf abschließen</span>
                    <span wire:loading wire:target="completeSale">Wird abgeschlossen …</span>
                </button>
            </div>
        </div>
    @endif

    @if ($lastSaleNumber)
        <div class="pos-dialog-backdrop fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4">
            <div class="pos-dialog-card pos-dialog-card--compact pos-sale-complete" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom));">
                <p class="pos-eyebrow pos-eyebrow--success">Verkauf abgeschlossen</p>
                <h2 class="pos-sale-number">{{ $lastSaleNumber }}</h2>

                <div class="pos-sale-metrics">
                    <div class="pos-sale-metric">
                        <p class="pos-sale-metric__label">Gesamt</p>
                        <p class="pos-sale-metric__value">{{ Money::format($lastSaleTotalCents ?? 0, $currency) }}</p>
                    </div>
                    <div class="pos-sale-metric pos-sale-metric--change">
                        <p class="pos-sale-metric__label">Rückgeld</p>
                        <p class="pos-sale-metric__value">{{ Money::format($lastChangeCents ?? 0, $currency) }}</p>
                    </div>
                </div>

                <button type="button" wire:click="$set('lastSaleNumber', null)" class="pos-dialog-action">
                    Nächster Verkauf
                </button>
            </div>
        </div>
    @endif
</div>
