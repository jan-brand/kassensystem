@php use App\Support\Money; @endphp
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
<h2 class="text-3xl font-black">Katalog</h2>
@if($notice)<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-bold text-emerald-900">{{ $notice }}</div>@endif
@if($screenError)<div class="mt-4 rounded-xl bg-red-50 p-3 font-bold text-red-900">{{ $screenError }}</div>@endif

<div class="mt-6 grid gap-6 xl:grid-cols-[340px_1fr]">
<div class="space-y-5">
<section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
<h3 class="font-black">Kategorie anlegen</h3>
<div class="mt-3 space-y-2">
<input wire:model="categoryName" placeholder="Name" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="categorySortOrder" type="number" min="0" placeholder="Sortierung" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="createCategory" class="w-full rounded-xl bg-slate-950 px-4 py-3 font-black text-white">Anlegen</button>
</div>
</section>

<section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
<h3 class="font-black">Produkt anlegen</h3>
<div class="mt-3 space-y-2">
<select wire:model="productCategoryId" class="w-full rounded-xl border border-slate-300 px-3 py-2"><option value="">Kategorie</option>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
<input wire:model="productName" placeholder="Name" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="productShortName" placeholder="Kurzname" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="productPrice" inputmode="decimal" placeholder="Preis z. B. 1,50" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="productSortOrder" type="number" min="0" placeholder="Sortierung" class="w-full rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="createProduct" class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-black text-white">Produkt anlegen</button>
</div>
</section>
</div>

<div class="space-y-4">
@forelse($categories as $category)
<section class="rounded-3xl bg-white p-5 ring-1 ring-slate-200">
<div class="flex items-center justify-between gap-3">
<div><h3 class="text-xl font-black">{{ $category->name }}</h3><p class="text-sm text-slate-500">Sortierung {{ $category->sort_order }}</p></div>
<button wire:click="startEditCategory({{ $category->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Kategorie bearbeiten</button>
</div>
@if($editingCategoryId === $category->id)
<div class="mt-3 grid gap-2 sm:grid-cols-[1fr_120px_auto]">
<input wire:model="editCategoryName" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editCategorySortOrder" type="number" min="0" class="rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="saveCategory" class="rounded-xl bg-slate-950 px-4 py-2 font-bold text-white">Speichern</button>
</div>
@endif

<div class="mt-4 divide-y divide-slate-100">
@forelse($category->products as $product)
<div class="py-3">
@if($editingProductId === $product->id)
<div class="grid gap-2 md:grid-cols-2">
<select wire:model="editProductCategoryId" class="rounded-xl border border-slate-300 px-3 py-2">@foreach($categories as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>@endforeach</select>
<input wire:model="editProductName" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editProductShortName" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editProductPrice" inputmode="decimal" class="rounded-xl border border-slate-300 px-3 py-2">
<input wire:model="editProductSortOrder" type="number" min="0" class="rounded-xl border border-slate-300 px-3 py-2">
<button wire:click="saveProduct" class="rounded-xl bg-slate-950 px-4 py-2 font-bold text-white">Produkt speichern</button>
</div>
@else
<div class="flex flex-wrap items-center justify-between gap-3">
<div><p class="font-black">{{ $product->name }} @if(!$product->active)<span class="text-sm text-slate-400">(inaktiv)</span>@endif</p><p class="text-sm text-slate-500">{{ $product->short_name }} · {{ Money::format($product->price_cents, $currency) }}</p></div>
<div class="flex gap-2"><button wire:click="startEditProduct({{ $product->id }})" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-bold">Bearbeiten</button><button wire:click="toggleProduct({{ $product->id }})" class="rounded-xl bg-slate-100 px-3 py-2 text-sm font-bold">{{ $product->active ? 'Deaktivieren' : 'Aktivieren' }}</button></div>
</div>
@endif
</div>
@empty<div class="py-4 text-slate-500">Noch keine Produkte.</div>@endforelse
</div>
</section>
@empty<div class="rounded-3xl bg-white p-8 text-center text-slate-500 ring-1 ring-slate-200">Noch keine Kategorien.</div>@endforelse
</div>
</div>
</main>
