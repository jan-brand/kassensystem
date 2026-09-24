<?php

namespace App\Surfaces\Pos\Livewire;

use App\Modules\Identity\Actions\AuthenticateWithPinAction;
use App\Modules\Identity\Exceptions\InvalidPin;
use App\Modules\Identity\Exceptions\PinRateLimited;
use App\Modules\Identity\Exceptions\UserInactive;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserLandingRouteService;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.pos')]
final class LoginScreen extends Component
{
    public string $username = '';

    public string $pin = '';

    public ?string $loginError = null;

    public function mount(UserLandingRouteService $landingRoute): void
    {
        $user = Auth::user();

        if ($user instanceof User) {
            $this->redirectRoute($landingRoute->for($user), navigate: true);
        }
    }

    public function login(
        AuthenticateWithPinAction $authenticate,
        UserLandingRouteService $landingRoute,
    ): void {
        $this->loginError = null;

        $validated = $this->validate([
            'username' => ['required', 'string', 'max:80'],
            'pin' => ['required', 'regex:/^\d{6}$/D'],
        ], [
            'username.required' => 'Bitte Benutzername eingeben.',
            'pin.required' => 'Bitte PIN eingeben.',
            'pin.regex' => 'Die PIN muss aus genau 6 Ziffern bestehen.',
        ]);

        try {
            $user = $authenticate->execute(
                username: $validated['username'],
                pin: $validated['pin'],
                ipAddress: request()->ip(),
                userAgent: request()->userAgent(),
            );
        } catch (InvalidPin) {
            $this->pin = '';
            $this->loginError = 'Benutzername oder PIN sind falsch.';

            return;
        } catch (UserInactive) {
            $this->pin = '';
            $this->loginError = 'Dieses Benutzerkonto ist deaktiviert.';

            return;
        } catch (PinRateLimited) {
            $this->pin = '';
            $this->loginError = 'Zu viele Fehlversuche. Bitte kurz warten und erneut versuchen.';

            return;
        }

        Session::regenerate();
        $this->redirectRoute($landingRoute->for($user), navigate: true);
    }

    public function render(GetSystemSettingsQuery $settingsQuery): View
    {
        return view('surfaces.pos.login', [
            'settings' => $settingsQuery->execute(),
        ]);
    }
}
