<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(Auth::check() ? 'pos.register' : 'login');
})->name('home');

Route::get('/health', function () {
    try {
        DB::connection()->select('SELECT 1');

        return response()->json([
            'status' => 'ok',
            'database' => 'ok',
            'timestamp' => now()->utc()->toIso8601String(),
        ]);
    } catch (Throwable) {
        return response()->json([
            'status' => 'unavailable',
            'database' => 'unavailable',
            'timestamp' => now()->utc()->toIso8601String(),
        ], 503);
    }
})->name('health');
