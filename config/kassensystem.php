<?php

return [
    'currency' => env('KASSENSYSTEM_CURRENCY', 'EUR'),
    'timezone' => env('KASSENSYSTEM_TIMEZONE', 'Europe/Berlin'),
    'pin_length' => (int) env('KASSENSYSTEM_PIN_LENGTH', 6),
    'pin_max_attempts' => (int) env('KASSENSYSTEM_PIN_MAX_ATTEMPTS', 5),
    'pin_lock_seconds' => (int) env('KASSENSYSTEM_PIN_LOCK_SECONDS', 60),
];
