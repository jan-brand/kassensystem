<?php

namespace App\Http\Controllers;

use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserLandingRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class HomeController
{
    public function __invoke(UserLandingRouteService $landingRoute): RedirectResponse
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        return redirect()->route($landingRoute->for($user));
    }
}
