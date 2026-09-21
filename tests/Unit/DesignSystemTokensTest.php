<?php

it('defines the required gastro design system tokens', function () {
    $path = resource_path('design/tokens/tokens.json');
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $tokens = $data['tokens'] ?? [];

    $required = [
        'raw.color.charcoal.950',
        'raw.color.cream.50',
        'raw.color.fir.900',
        'raw.color.sage.300',
        'raw.color.mustard.500',
        'raw.color.red.600',
        'raw.color.green.600',
        'bg.main',
        'bg.panel',
        'bg.navigation',
        'text.primary',
        'text.secondary',
        'accent.active',
        'action.success',
        'action.danger',
        'focus.ring',
        'touch.min',
        'touch.comfort',
    ];

    foreach ($required as $token) {
        expect($tokens)->toHaveKey($token);
    }
});

it('keeps raw colors as hex values and semantic colors as aliases', function () {
    $path = resource_path('design/tokens/tokens.json');
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $tokens = $data['tokens'] ?? [];

    foreach ($tokens as $name => $value) {
        if (str_starts_with($name, 'raw.color.')) {
            expect($value)->toMatch('/^#[0-9A-F]{6}$/i');
        }
    }

    foreach ([
        'bg.main',
        'bg.panel',
        'bg.navigation',
        'text.primary',
        'text.secondary',
        'accent.active',
        'action.success',
        'action.danger',
        'focus.ring',
    ] as $name) {
        expect((string) $tokens[$name])->toStartWith('var(--');
    }
});
