<div class="pos-login">
    <section class="pos-login__card">
        <div class="pos-login__identity">
            @if ($settings?->logo_path)
                <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Logo" class="pos-login__logo">
            @endif
            <p class="pos-login__eyebrow">{{ $settings?->cafeteria_name ?: 'Kassensystem' }}</p>
            <h1 class="pos-login__title">Anmelden</h1>
            <p class="pos-login__copy">Mit Benutzername und 6-stelliger PIN anmelden.</p>
        </div>

        <form wire:submit="login" class="pos-login__form">
            <div>
                <label for="username" class="pos-login__label">Benutzername</label>
                <input
                    id="username"
                    type="text"
                    wire:model="username"
                    autocomplete="username"
                    autofocus
                    class="pos-login__input"
                >
                @error('username')
                    <p class="pos-login__field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pin" class="pos-login__label">PIN</label>
                <input
                    id="pin"
                    type="password"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    wire:model="pin"
                    autocomplete="current-password"
                    class="pos-login__input pos-login__input--pin"
                >
                @error('pin')
                    <p class="pos-login__field-error">{{ $message }}</p>
                @enderror
            </div>

            @if ($loginError)
                <div class="pos-login__alert" role="alert">
                    {{ $loginError }}
                </div>
            @endif

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="pos-login__submit disabled:cursor-wait"
            >
                <span wire:loading.remove wire:target="login">Anmelden</span>
                <span wire:loading wire:target="login">Anmeldung läuft …</span>
            </button>
        </form>
    </section>
</div>
