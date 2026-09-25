<?php

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Models\Sale;
use App\Surfaces\Administration\Livewire\DiscountOffersScreen;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('protects offer administration and lets a manager apply a manual pos discount', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'surface-discount-cashier',
        '123456',
        'Casey',
        'Cashier',
        UserRole::Cashier,
    );
    $manager = app(CreateUserAction::class)->execute(
        'surface-discount-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $category = Category::query()->create([
        'name' => 'Rabattoberfläche',
        'active' => true,
        'sort_order' => 1,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Oberflächenprodukt',
        'short_name' => 'Oberfläche',
        'price_cents' => 500,
        'is_consumable' => false,
        'active' => true,
        'sort_order' => 1,
    ]);

    $this->actingAs($cashier)
        ->get(route('administration.discount-offers'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('administration.discount-offers'))
        ->assertOk()
        ->assertSee('Tagesangebote');

    $this->actingAs($manager);

    Livewire::test(DiscountOffersScreen::class)
        ->set('productId', $product->id)
        ->set('name', 'Manager-Angebot')
        ->set('discountType', DiscountType::FixedAmount->value)
        ->set('discountValue', '1,00')
        ->call('save')
        ->assertSet('screenError', null);

    $component = Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->assertSet('screenError', null);

    $sale = Sale::query()->with('items')->firstOrFail();
    $item = $sale->items->firstOrFail();

    $component
        ->call('openDiscount', $item->id)
        ->set('discountType', DiscountType::NewPrice->value)
        ->set('discountValue', '2,00')
        ->call('applyManualDiscount')
        ->assertSet('screenError', null);

    $item->refresh();

    expect($item->original_unit_price_cents)->toBe(500)
        ->and($item->unit_price_cents)->toBe(200)
        ->and($item->discount_source)->toBe(DiscountSource::Manual)
        ->and($item->discount_offer_id)->toBeNull();
});
