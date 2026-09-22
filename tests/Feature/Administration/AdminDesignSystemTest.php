<?php

it('uses the shared administration page layer across every administration surface', function () {
    $layout = file_get_contents(resource_path('views/layouts/administration.blade.php'));

    expect($layout)
        ->toContain('class="admin-shell"')
        ->toContain('class="admin-header"')
        ->toContain('admin-nav__link');

    foreach ([
        'dashboard.blade.php',
        'catalog.blade.php',
        'cashiers.blade.php',
        'sales.blade.php',
        'reporting.blade.php',
        'settings.blade.php',
        'audit.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/surfaces/administration/'.$view));

        expect($contents)->toContain('class="admin-page');
    }
});

it('keeps administration design styles token driven', function () {
    $patterns = file_get_contents(resource_path('css/patterns/index.css'));
    $shellCss = file_get_contents(resource_path('css/patterns/admin-shell.css'));
    $controlsCss = file_get_contents(resource_path('css/patterns/admin-controls.css'));

    expect($patterns)
        ->toContain("@import './admin-shell.css';")
        ->toContain("@import './admin-controls.css';")
        ->and(preg_match('/#[0-9a-fA-F]{3,8}\\b/', $shellCss))->toBe(0)
        ->and(preg_match('/#[0-9a-fA-F]{3,8}\\b/', $controlsCss))->toBe(0)
        ->and($controlsCss)
        ->toContain('var(--raw-color-black)')
        ->toContain('var(--raw-color-white)')
        ->toContain('var(--accent-active)');
});

it('uses shared administration overlay classes for detail dialogs', function () {
    $sales = file_get_contents(resource_path('views/surfaces/administration/sales.blade.php'));
    $audit = file_get_contents(resource_path('views/surfaces/administration/audit.blade.php'));

    expect($sales)
        ->toContain('admin-overlay')
        ->toContain('admin-receipt')
        ->and($audit)
        ->toContain('admin-overlay')
        ->toContain('admin-dialog');
});
