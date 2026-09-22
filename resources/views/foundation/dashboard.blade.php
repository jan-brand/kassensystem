<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ffffff">
    <title>Foundation Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="public-shell">
<main class="public-page">
    <div class="public-dashboard__header">
        <div>
            <p class="public-kicker">Development only</p>
            <h1 class="public-title">Foundation Dashboard</h1>
        </div>
        <a href="/" class="public-action">Zur Anwendung</a>
    </div>

    <div class="public-dashboard__grid public-dashboard__grid--stats">
        @foreach ([['Module', count($modules)], ['Surfaces', count($surfaces)], ['Pages', count($pages)], ['Design', count($design)], ['Permissions', count($permissions)]] as [$label, $count])
            <section class="public-dashboard__card">
                <div class="public-dashboard__value">{{ $count }}</div>
                <div class="public-dashboard__label">{{ $label }}</div>
            </section>
        @endforeach
    </div>

    <div class="public-dashboard__grid public-dashboard__grid--content">
        @foreach ([['Module', $modules], ['Surfaces', $surfaces], ['Pages', $pages], ['Design System', $design]] as [$title, $items])
            <section class="public-dashboard__card">
                <h2 class="text-xl font-semibold">{{ $title }}</h2>
                <ul class="public-dashboard__list">
                    @forelse ($items as $item)
                        <li class="public-dashboard__item">
                            <strong>{{ $item['name'] ?? '?' }}</strong>
                            @if(isset($item['type']))
                                <span class="public-dashboard__meta">· {{ $item['type'] }}</span>
                            @endif
                        </li>
                    @empty
                        <li class="public-dashboard__meta">Noch keine Einträge.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</main>
</body>
</html>
