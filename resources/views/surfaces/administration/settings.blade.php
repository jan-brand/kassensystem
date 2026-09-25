<main class="admin-page admin-page--narrow">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-slate-500">Administration</p>
            <h2 class="mt-1 text-3xl font-black">Systemeinstellungen</h2>
            <p class="mt-2 max-w-2xl text-sm text-slate-600">
                Diese Werte steuern die sichtbare Bezeichnung der Cafeteria, den Kassenplatz und die Darstellung im POS.
            </p>
        </div>
    </div>

    @if ($notice)
        <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-bold text-emerald-900">
            {{ $notice }}
        </div>
    @endif

    @if ($screenError)
        <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 font-bold text-red-900">
            {{ $screenError }}
        </div>
    @endif

    <form wire:submit="save" class="mt-6 grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="text-lg font-black">Bezeichnungen</h3>
                <p class="mt-1 text-sm text-slate-500">Die Änderungen werden direkt in der Kassenoberfläche verwendet.</p>

                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="cafeteriaName" class="mb-2 block text-sm font-bold">Cafeteria-/Systemname</label>
                        <input
                            id="cafeteriaName"
                            wire:model="cafeteriaName"
                            maxlength="160"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                        >
                        @error('cafeteriaName')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="registerName" class="mb-2 block text-sm font-bold">Kassenname</label>
                        <input
                            id="registerName"
                            wire:model="registerName"
                            maxlength="120"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                        >
                        @error('registerName')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="text-lg font-black">POS-Darstellung</h3>
                <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl bg-slate-50 p-4">
                    <input wire:model="posShowShortNames" type="checkbox" class="mt-1 h-5 w-5 rounded border-slate-300">
                    <span>
                        <span class="block font-bold">Kurznamen auf Produktkacheln anzeigen</span>
                        <span class="mt-1 block text-sm text-slate-600">
                            Aktiv: z. B. „Schorle“. Deaktiviert: der vollständige Produktname wird angezeigt.
                        </span>
                    </span>
                </label>
            </section>

            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="text-lg font-black">PayPal.me</h3>
                <p class="mt-1 text-sm text-slate-500">Optionales Standortkonto für manuell bestätigte PayPal.me-Zahlungen im POS.</p>

                <div class="mt-5">
                    <label for="paypalMeHandle" class="mb-2 block text-sm font-bold">PayPal.me-Name</label>
                    <div class="flex overflow-hidden rounded-2xl border border-slate-300 bg-white focus-within:border-slate-950 focus-within:ring-4 focus-within:ring-slate-950/10">
                        <span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-sm font-semibold text-slate-500">paypal.me/</span>
                        <input
                            id="paypalMeHandle"
                            wire:model="paypalMeHandle"
                            maxlength="20"
                            autocomplete="off"
                            placeholder="schoolcafe"
                            class="min-w-0 flex-1 border-0 px-4 py-3 outline-none"
                        >
                    </div>
                    @error('paypalMeHandle')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs leading-5 text-slate-500">Nur den Namen hinter paypal.me/ eintragen, keine vollständige URL.</p>
                </div>

                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                    Der POS erzeugt daraus einen QR-Code mit dem exakten Betrag. Das Anzeigen des QR-Codes ist noch keine Zahlung; Mitarbeiter bestätigen erst nach Sichtprüfung.
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="text-lg font-black">Digitale Belege</h3>
                <p class="mt-1 text-sm text-slate-500">Öffentliche Beleglinks laufen automatisch nach der festgelegten Zeit ab.</p>

                <label class="mt-5 block">
                    <span class="mb-2 block text-sm font-bold">Aufbewahrungsdauer in Tagen</span>
                    <input
                        wire:model="receiptRetentionDays"
                        type="number"
                        min="1"
                        max="3650"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 outline-none focus:border-slate-950 focus:ring-4 focus:ring-slate-950/10"
                    >
                    @error('receiptRetentionDays')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                </label>
                <p class="mt-2 text-xs leading-5 text-slate-500">
                    Der Zeitraum wird beim Ausstellen festgeschrieben. Interne Verkaufs- und Auditdaten bleiben davon unberührt.
                </p>
            </section>

            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="text-lg font-black">Logo</h3>
                <p class="mt-1 text-sm text-slate-500">Optional. JPG, PNG oder WebP, maximal 2 MB.</p>

                @if ($logoUrl && ! $removeLogo)
                    <div class="mt-5 flex items-center gap-4 rounded-2xl border border-slate-200 p-4">
                        <img src="{{ $logoUrl }}" alt="Aktuelles Logo" class="h-20 w-20 rounded-2xl object-contain ring-1 ring-slate-200">
                        <div class="min-w-0">
                            <p class="font-bold">Aktuelles Logo</p>
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $existingLogoPath }}</p>
                        </div>
                    </div>
                @endif

                <div class="mt-5">
                    <input
                        wire:model="logoUpload"
                        type="file"
                        accept="image/jpeg,image/png,image/webp"
                        class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm"
                    >
                    @error('logoUpload')<p class="mt-2 text-sm font-semibold text-red-700">{{ $message }}</p>@enderror
                </div>

                @if ($existingLogoPath)
                    <label class="mt-4 flex items-center gap-3 text-sm font-semibold">
                        <input wire:model="removeLogo" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                        Vorhandenes Logo entfernen
                    </label>
                @endif
            </section>
        </div>

        <aside class="space-y-6">
            <section class="rounded-3xl bg-white p-6 ring-1 ring-slate-200">
                <h3 class="font-black">Technische Vorgaben</h3>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="font-semibold text-slate-500">Währung</dt>
                        <dd class="mt-1 text-lg font-black">{{ $currency }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">Zeitzone</dt>
                        <dd class="mt-1 font-black">{{ $timezone }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs leading-5 text-slate-500">
                    Diese Werte bleiben bewusst in der technischen Konfiguration und können hier nicht geändert werden.
                </p>
            </section>

            <section class="rounded-3xl bg-slate-950 p-6 text-white">
                <h3 class="font-black">Speichern</h3>
                <p class="mt-2 text-sm text-slate-300">Änderungen werden protokolliert und sind nach dem Speichern direkt im POS sichtbar.</p>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="mt-5 w-full rounded-2xl bg-white px-4 py-3 font-black text-slate-950 disabled:cursor-wait disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="save">Einstellungen speichern</span>
                    <span wire:loading wire:target="save">Speichert …</span>
                </button>
            </section>

            @if ($settings)
                <section class="rounded-3xl bg-white p-6 text-sm ring-1 ring-slate-200">
                    <p class="font-bold text-slate-500">Zuletzt geändert</p>
                    <p class="mt-2 font-black">{{ $settings->updated_at?->format('d.m.Y H:i') }}</p>
                    @if ($settings->updatedBy)
                        <p class="mt-1 text-slate-600">{{ $settings->updatedBy->auditDisplayName() }}</p>
                    @endif
                </section>
            @endif
        </aside>
    </form>
</main>
