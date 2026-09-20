<?php

use App\Support\Money;

it('parses euro amounts without floats', function () {
    expect(Money::parseCents('12,34'))->toBe(1234)
        ->and(Money::parseCents('12.34'))->toBe(1234)
        ->and(Money::parseCents('1.234,56 €'))->toBe(123456)
        ->and(Money::parseCents('5'))->toBe(500);
});

it('formats integer cents for display', function () {
    expect(Money::format(123456))->toBe('1.234,56 €')
        ->and(Money::format(-42))->toBe('-0,42 €')
        ->and(Money::decimal(505))->toBe('5,05');
});
