<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl shadow-slate-200/70 ring-1 ring-slate-200">
        <div class="mb-8">
            @if ($settings?->logo_path)
                <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Logo" class="mb-5 h-16 w-16 rounded-2xl object-contain ring-1 ring-slate-200">
            @endif
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $settings?->cafeteria_name ?: 'Kassensystem' }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Anmelden</h1>
            <p class="mt-2 text-sm text-slate-600">Mit Benutzername und 6-stelliger PIN anmelden.</p>
        </div>

        <form wire:submit="login" class="space-y-5">
            <div>
                <label for="username" class="mb-2 block text-sm font-semibold">Benutzername</label>
                <input
                    id="username"
                    type="text"
                    wire:model="username"
                    autocomplete="username"
                    autofocus
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-lg outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10"
                >
                @error('username')
                    <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pin" class="mb-2 block text-sm font-semibold">PIN</label>
                <input
                    id="pin"
                    type="password"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    wire:model="pin"
                    autocomplete="current-password"
                    class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-center text-2xl tracking-[0.4em] outline-none transition focus:border-slate-900 focus:ring-4 focus:ring-slate-900/10"
                >
                @error('pin')
                    <p class="mt-2 text-sm font-medium text-red-700">{{ $message }}</p>
                @enderror
            </div>

            @if ($loginError)
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ $loginError }}
                </div>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="w-full rounded-2xl bg-slate-950 px-5 py-4 text-lg font-bold text-white transition hover:bg-slate-800 disabled:cursor-wait disabled:opacity-60"
            >
                <span wire:loading.remove wire:target="login">Anmelden</span>
                <span wire:loading wire:target="login">Anmeldung läuft …</span>
            </button>
        </form>
    </div>
</div>
