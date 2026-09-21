<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title>Administration · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-[100dvh] bg-slate-100 text-slate-950">
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur" style="padding-top: env(safe-area-inset-top);">
    <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Kassensystem</p>
                <h1 class="truncate text-lg font-black sm:text-xl">Administration</h1>
            </div>

            <nav class="hidden flex-wrap items-center justify-end gap-1.5 text-sm font-bold md:flex">
                @can('administration.access')
                    <a href="{{ route('administration.dashboard') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Übersicht</a>
                @endcan
                @can('catalog.manage')
                    <a href="{{ route('administration.catalog') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Katalog</a>
                @endcan
                @can('users.cashiers.manage')
                    <a href="{{ route('administration.cashiers') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Kassierer</a>
                @endcan
                @can('sales.view')
                    <a href="{{ route('administration.sales') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Verkäufe</a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('administration.reporting') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Berichte</a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('administration.settings') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Einstellungen</a>
                @endcan
                @can('audit.view')
                    <a href="{{ route('administration.audit') }}" wire:navigate class="rounded-xl px-3 py-2 hover:bg-slate-100">Audit</a>
                @endcan
                @can('pos.access')
                    <a href="{{ route('pos.register') }}" wire:navigate class="rounded-xl border border-slate-300 px-3 py-2 hover:bg-slate-50">Zur Kasse</a>
                @endcan
            </nav>

            <details class="relative md:hidden">
                <summary class="cursor-pointer list-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black shadow-sm">
                    Menü
                </summary>
                <nav class="absolute right-0 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 text-sm font-bold shadow-xl">
                    @can('administration.access')
                        <a href="{{ route('administration.dashboard') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Übersicht</a>
                    @endcan
                    @can('catalog.manage')
                        <a href="{{ route('administration.catalog') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Katalog</a>
                    @endcan
                    @can('users.cashiers.manage')
                        <a href="{{ route('administration.cashiers') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Kassierer</a>
                    @endcan
                    @can('sales.view')
                        <a href="{{ route('administration.sales') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Verkäufe</a>
                    @endcan
                    @can('reports.view')
                        <a href="{{ route('administration.reporting') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Berichte</a>
                    @endcan
                    @can('settings.manage')
                        <a href="{{ route('administration.settings') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Einstellungen</a>
                    @endcan
                    @can('audit.view')
                        <a href="{{ route('administration.audit') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Audit</a>
                    @endcan
                    @can('pos.access')
                        <div class="my-1 border-t border-slate-200"></div>
                        <a href="{{ route('pos.register') }}" wire:navigate class="block rounded-xl px-3 py-3 hover:bg-slate-100">Zur Kasse</a>
                    @endcan
                </nav>
            </details>
        </div>
    </div>
</header>
{{ $slot }}
@livewireScripts
</body>
</html>
