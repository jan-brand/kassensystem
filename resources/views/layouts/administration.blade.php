<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-950">
<header class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 sm:px-6">
        <div><p class="text-xs font-bold uppercase tracking-widest text-slate-500">Kassensystem</p><h1 class="text-xl font-black">Administration</h1></div>
        <nav class="flex flex-wrap gap-2 text-sm font-bold">
            <a href="{{ route('administration.dashboard') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Übersicht</a>
            <a href="{{ route('administration.catalog') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Katalog</a>
            <a href="{{ route('administration.cashiers') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Kassierer</a>
            <a href="{{ route('administration.reporting') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Berichte</a>
            <a href="{{ route('administration.settings') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Einstellungen</a>
            <a href="{{ route('pos.register') }}" wire:navigate class="rounded-xl border border-slate-300 px-3 py-2">Zur Kasse</a>
        </nav>
    </div>
</header>
{{ $slot }}
@livewireScripts
</body>
</html>
