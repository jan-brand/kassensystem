<?php

it('loads the extracted POS component styles', function () {
    $index = file_get_contents(resource_path('css/components/index.css'));

    foreach ([
        'button.css',
        'form-field.css',
        'category-tab.css',
        'product-tile.css',
        'cart-line.css',
        'quantity-stepper.css',
        'payment-action.css',
        'bottom-sheet.css',
        'pos-terminal.css',
    ] as $component) {
        expect($index)->toContain("@import './{$component}';");
    }
});

it('keeps component styles free from hard coded hex colours', function () {
    foreach (glob(resource_path('css/components/*.css')) ?: [] as $file) {
        $css = file_get_contents($file);

        expect(preg_match('/#[0-9a-fA-F]{3,8}\\b/', $css))->toBe(0);
    }
});

it('uses tokens css as the canonical stylesheet token source', function () {
    $appCss = file_get_contents(resource_path('css/app.css'));

    expect($appCss)
        ->toContain("@import './tokens.css';")
        ->not->toContain('foundation-tokens.css');
});
