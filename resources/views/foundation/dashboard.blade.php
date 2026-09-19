<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Foundation Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900">
<main class="mx-auto max-w-6xl px-6 py-10">
    <div class="flex items-end justify-between gap-4">
        <div><p class="text-sm uppercase tracking-wider text-slate-500">Development only</p><h1 class="text-3xl font-bold">Foundation Dashboard</h1></div>
        <a href="/" class="text-sm underline">Zur Anwendung</a>
    </div>
    <div class="mt-8 grid gap-4 md:grid-cols-5">
        @foreach ([['Module', count($modules)], ['Surfaces', count($surfaces)], ['Pages', count($pages)], ['Design', count($design)], ['Permissions', count($permissions)]] as [$label, $count])
            <section class="rounded-xl border bg-white p-5"><div class="text-3xl font-bold">{{ $count }}</div><div class="mt-1 text-sm text-slate-500">{{ $label }}</div></section>
        @endforeach
    </div>
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @foreach ([['Module', $modules], ['Surfaces', $surfaces], ['Pages', $pages], ['Design System', $design]] as [$title, $items])
            <section class="rounded-xl border bg-white p-6"><h2 class="text-xl font-semibold">{{ $title }}</h2><ul class="mt-4 space-y-2 text-sm">@forelse ($items as $item)<li class="rounded bg-slate-50 px-3 py-2"><strong>{{ $item['name'] ?? '?' }}</strong>@if(isset($item['type'])) <span class="text-slate-500">· {{ $item['type'] }}</span>@endif</li>@empty<li class="text-slate-500">Noch keine Einträge.</li>@endforelse</ul></section>
        @endforeach
    </div>
</main>
</body>
</html>
