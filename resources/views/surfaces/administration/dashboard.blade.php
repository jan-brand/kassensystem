@php use App\Support\Money; @endphp
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <h2 class="text-3xl font-black">Übersicht</h2>
    <p class="mt-1 text-slate-500">{{ $date }}</p>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-200"><p class="text-sm font-bold text-slate-500">Tagesumsatz</p><p class="mt-2 text-3xl font-black">{{ Money::format($summary['revenue_cents']) }}</p></div>
        <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-200"><p class="text-sm font-bold text-slate-500">Verkäufe</p><p class="mt-2 text-3xl font-black">{{ $summary['sales_count'] }}</p></div>
        <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-200"><p class="text-sm font-bold text-slate-500">Aktive Produkte</p><p class="mt-2 text-3xl font-black">{{ $activeProducts }}</p></div>
        <div class="rounded-3xl bg-white p-6 ring-1 ring-slate-200"><p class="text-sm font-bold text-slate-500">Kassierer</p><p class="mt-2 text-3xl font-black">{{ $cashiers }}</p></div>
    </div>
</main>
