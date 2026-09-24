<!doctype html>
<html lang="de" data-theme="gastro-dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#000000">
    <title>Kellner · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-[100dvh] bg-black text-white antialiased">
    <nav class="border-b border-white/10 bg-black px-4 py-2">
        <div class="mx-auto flex max-w-[1600px] gap-2">
            @can('hospitality.access')
                <a href="{{ route('waiter.service') }}" wire:navigate class="rounded-xl px-4 py-2 text-sm font-black {{ request()->routeIs('waiter.service') ? 'bg-white text-black' : 'bg-white/5 text-white' }}">Tische</a>
            @endcan
            @can('tickets.redeem')
                <a href="{{ route('waiter.tickets') }}" wire:navigate class="rounded-xl px-4 py-2 text-sm font-black {{ request()->routeIs('waiter.tickets') ? 'bg-white text-black' : 'bg-white/5 text-white' }}">Tickets</a>
            @endcan
        </div>
    </nav>
    {{ $slot }}
    @livewireScripts
</body>
</html>
