<?php

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function mobilePosFixture(string $username): array
{
    $user = app(CreateUserAction::class)->execute(
        username: $username,
        pin: '123456',
        firstName: 'Mobile',
        lastName: 'Cashier',
    );

    $category = Category::query()->create([
        'name' => 'Mobil',
        'active' => true,
        'sort_order' => 10,
    ]);

    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'Testprodukt Mobil',
        'short_name' => 'Mobil',
        'price_cents' => 150,
        'active' => true,
        'sort_order' => 10,
    ]);

    return [$user, $product];
}

it('keeps one open sale while repeated touch adds aggregate the quantity', function () {
    [$user, $product] = mobilePosFixture('mobile-repeat');
    $this->actingAs($user);

    Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('addProduct', $product->id)
        ->call('addProduct', $product->id)
        ->call('openMobileCart')
        ->assertSet('mobileCartOpen', true)
        ->assertSeeHtml('data-pos-mobile-cart')
        ->call('closeMobileCart')
        ->assertSet('mobileCartOpen', false);

    $sale = Sale::query()->with('items')->sole();

    expect(Sale::query()->count())->toBe(1)
        ->and($sale->status)->toBe(SaleStatus::Open)
        ->and($sale->items)->toHaveCount(1)
        ->and($sale->items->first()->quantity)->toBe(3)
        ->and($sale->total_cents)->toBe(450);
});

it('closes the mobile cart when payment starts and completes only one payment', function () {
    [$user, $product] = mobilePosFixture('mobile-payment');
    $this->actingAs($user);

    $component = Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('openMobileCart')
        ->assertSet('mobileCartOpen', true)
        ->call('showPayment')
        ->assertSet('mobileCartOpen', false)
        ->assertSet('paymentOpen', true)
        ->set('receivedAmount', '2,00')
        ->call('completeSale')
        ->assertSet('mobileCartOpen', false)
        ->assertSet('paymentOpen', false)
        ->assertSet('screenError', null);

    $sale = Sale::query()->sole();

    expect($sale->status)->toBe(SaleStatus::Completed)
        ->and(Payment::query()->where('sale_id', $sale->id)->count())->toBe(1)
        ->and(Payment::query()->where('sale_id', $sale->id)->value('change_cents'))->toBe(50);

    $component
        ->call('completeSale')
        ->assertSet('screenError', 'Es gibt keinen offenen Verkauf.');

    expect(Sale::query()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(1);
});

it('renders the mobile action bar for an open register', function () {
    [$user, $product] = mobilePosFixture('mobile-render');
    $this->actingAs($user);

    Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->assertSeeHtml('data-pos-mobile-bar')
        ->assertSee('1 Artikel')
        ->assertSee('1,50 €')
        ->assertSee('Bezahlen');
});
