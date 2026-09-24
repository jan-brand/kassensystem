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
    {{ $slot }}
    @livewireScripts
</body>
</html>
