<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-shell">
<main class="public-page">
    <p class="public-kicker">Kassensystem</p>
    <h1 class="public-title">Technische Projektübersicht.</h1>
    <p class="public-copy">Foundation-Werkzeuge für Module, Designsystem, Surfaces, Umgebungen und Architekturchecks bleiben für die lokale Entwicklung verfügbar.</p>
    @if (Route::has('foundation.dashboard'))
        <a class="public-action" href="{{ route('foundation.dashboard') }}">Foundation Dashboard</a>
    @endif
</main>
</body>
</html>
