<?php

it('uses the neutral POS design for login and register closing', function () {
    $components = file_get_contents(resource_path('css/components/index.css'));
    $login = file_get_contents(resource_path('views/surfaces/pos/login.blade.php'));
    $register = file_get_contents(resource_path('views/surfaces/pos/register.blade.php'));

    expect($components)
        ->toContain("@import './login.css';")
        ->toContain("@import './register-closing.css';")
        ->and($login)
        ->toContain('pos-login__card')
        ->toContain('pos-login__submit')
        ->not->toContain('shadow-slate-200/70')
        ->and($register)
        ->toContain('pos-closing__intro')
        ->toContain('pos-closing-stat--expected')
        ->not->toContain('border-amber-200 bg-amber-50');
});

it('keeps the new POS design components token driven', function () {
    foreach ([
        resource_path('css/components/login.css'),
        resource_path('css/components/register-closing.css'),
    ] as $file) {
        $css = file_get_contents($file);

        expect(preg_match('/#[0-9a-fA-F]{3,8}\b/', $css))->toBe(0);
    }
});

it('uses the neutral public surface for public and foundation views', function () {
    $patterns = file_get_contents(resource_path('css/patterns/index.css'));
    $publicLayout = file_get_contents(resource_path('views/layouts/public.blade.php'));
    $welcome = file_get_contents(resource_path('views/welcome.blade.php'));
    $foundation = file_get_contents(resource_path('views/foundation/dashboard.blade.php'));

    expect($patterns)
        ->toContain("@import './public-shell.css';")
        ->and($publicLayout)
        ->toContain('public-shell')
        ->and($welcome)
        ->toContain('public-shell')
        ->and($foundation)
        ->toContain('public-dashboard__card');
});

it('keeps fallback error pages on the neutral standalone error surface', function () {
    foreach ([403, 404, 500, 503] as $status) {
        $view = file_get_contents(resource_path("views/errors/{$status}.blade.php"));

        expect($view)
            ->toContain("data-error-surface=\"{$status}\"")
            ->toContain('background: #000000;')
            ->toContain('background: #0d0d0d;');
    }
});
