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
<body class="admin-shell">
<header class="admin-header" style="padding-top: env(safe-area-inset-top);">
    <div class="admin-header__inner">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <p class="admin-brand-kicker">Kassensystem</p>
                <h1 class="admin-brand-title truncate">Administration</h1>
            </div>

            <nav class="admin-nav hidden md:flex">
                @can('administration.access')
                    <a href="{{ route('administration.dashboard') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.dashboard') ? 'is-active' : '' }}">Übersicht</a>
                @endcan
                @can('catalog.manage')
                    <a href="{{ route('administration.catalog') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.catalog') ? 'is-active' : '' }}">Katalog</a>
                @endcan
                @can('users.cashiers.manage')
                    <a href="{{ route('administration.cashiers') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.cashiers') ? 'is-active' : '' }}">Kassierer</a>
                @endcan
                @can('users.waiters.manage')
                    <a href="{{ route('administration.waiters') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.waiters') ? 'is-active' : '' }}">Kellner</a>
                @endcan
                @can('hospitality.configuration.manage')
                    <a href="{{ route('administration.hospitality') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.hospitality') ? 'is-active' : '' }}">Gastro</a>
                @endcan
                @can('tickets.manage')
                    <a href="{{ route('administration.tickets') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.tickets') ? 'is-active' : '' }}">Tickets</a>
                @endcan
                @can('sales.view')
                    <a href="{{ route('administration.sales') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.sales') ? 'is-active' : '' }}">Verkäufe</a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('administration.reporting') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.reporting') ? 'is-active' : '' }}">Berichte</a>
                @endcan
                @can('settings.manage')
                    <a href="{{ route('administration.settings') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.settings') ? 'is-active' : '' }}">Einstellungen</a>
                @endcan
                @can('audit.view')
                    <a href="{{ route('administration.audit') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.audit') ? 'is-active' : '' }}">Audit</a>
                @endcan
                @can('hospitality.access')
                    <a href="{{ route('waiter.service') }}" wire:navigate class="admin-nav__link">Kellner-POS</a>
                @endcan
                @can('pos.access')
                    <a href="{{ route('pos.register') }}" wire:navigate class="admin-nav__link admin-nav__link--pos">Zur Kasse</a>
                @endcan
            </nav>

            <details class="relative md:hidden">
                <summary class="admin-mobile-summary list-none">
                    Menü
                </summary>
                <nav class="admin-mobile-nav">
                    @can('administration.access')
                        <a href="{{ route('administration.dashboard') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.dashboard') ? 'is-active' : '' }}">Übersicht</a>
                    @endcan
                    @can('catalog.manage')
                        <a href="{{ route('administration.catalog') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.catalog') ? 'is-active' : '' }}">Katalog</a>
                    @endcan
                    @can('users.cashiers.manage')
                        <a href="{{ route('administration.cashiers') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.cashiers') ? 'is-active' : '' }}">Kassierer</a>
                    @endcan
                    @can('tickets.manage')
                        <a href="{{ route('administration.tickets') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.tickets') ? 'is-active' : '' }}">Tickets</a>
                    @endcan
                    @can('sales.view')
                        <a href="{{ route('administration.sales') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.sales') ? 'is-active' : '' }}">Verkäufe</a>
                    @endcan
                    @can('reports.view')
                        <a href="{{ route('administration.reporting') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.reporting') ? 'is-active' : '' }}">Berichte</a>
                    @endcan
                    @can('settings.manage')
                        <a href="{{ route('administration.settings') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.settings') ? 'is-active' : '' }}">Einstellungen</a>
                    @endcan
                    @can('audit.view')
                        <a href="{{ route('administration.audit') }}" wire:navigate class="admin-nav__link {{ request()->routeIs('administration.audit') ? 'is-active' : '' }}">Audit</a>
                    @endcan
                    @can('pos.access')
                        <a href="{{ route('pos.register') }}" wire:navigate class="admin-nav__link admin-nav__link--pos">Zur Kasse</a>
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
