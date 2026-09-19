<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<main class="mx-auto max-w-4xl px-6 py-16">
    <p class="text-sm font-semibold uppercase tracking-widest text-slate-500">Laravel Webapp Foundation</p>
    <h1 class="mt-4 text-4xl font-bold">Ein neutrales Grundkonstrukt für Webanwendungen.</h1>
    <p class="mt-5 max-w-2xl text-lg text-slate-600">Module, Designsystem, Surfaces, Pages, Environment-Synchronisation und Architekturchecks sind als Entwicklungswerkzeuge integriert.</p>
    <div class="mt-8 flex gap-3">
        @if (Route::has('foundation.dashboard'))
            <a class="rounded-lg bg-slate-900 px-4 py-2 text-white" href="{{ route('foundation.dashboard') }}">Foundation Dashboard</a>
        @endif
    </div>
</main>
</body>
</html>
