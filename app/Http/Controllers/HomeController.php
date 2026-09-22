<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class HomeController
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route(
            Auth::check() ? 'pos.register' : 'login',
        );
    }
}
