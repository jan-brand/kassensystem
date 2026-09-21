@php use App\Support\Money; @endphp
<main class="admin-page">
    <h2 class="admin-page__heading">Übersicht</h2>
    <p class="admin-page__date">{{ $date }}</p>

    <div class="admin-stat-grid">
        <article class="admin-stat-card admin-stat-card--revenue">
            <p class="admin-stat-label">Tagesumsatz</p>
            <p class="admin-stat-value">{{ Money::format($summary['revenue_cents']) }}</p>
        </article>
        <article class="admin-stat-card">
            <p class="admin-stat-label">Verkäufe</p>
            <p class="admin-stat-value">{{ $summary['sales_count'] }}</p>
        </article>
        <article class="admin-stat-card">
            <p class="admin-stat-label">Aktive Produkte</p>
            <p class="admin-stat-value">{{ $activeProducts }}</p>
        </article>
        <article class="admin-stat-card">
            <p class="admin-stat-label">Kassierer</p>
            <p class="admin-stat-value">{{ $cashiers }}</p>
        </article>
    </div>
</main>
