<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows an amount-specific paypal qr without booking until manual confirmation', function () {
    $admin = app(CreateUserAction::class)->execute(
        'paypal-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        paypalMeHandle: 'schoolcafe',
    );

    $cashier = app(CreateUserAction::class)->execute(
        'paypal-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $category = Category::query()->create([
        'name' => 'PayPal',
        'active' => true,
        'sort_order' => 10,
    ]);
    $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => 'PayPal Artikel',
        'short_name' => 'PayPal',
        'price_cents' => 150,
        'active' => true,
        'sort_order' => 10,
    ]);

    $this->actingAs($cashier);

    $component = Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('showPayment')
        ->call('selectPaymentMethod', 'paypal')
        ->assertSet('paymentMethod', 'paypal')
        ->assertSee('https://paypal.me/schoolcafe/1.50EUR')
        ->assertSeeHtml('data-testid="paypal-payment-qr"');

    $sale = Sale::query()->sole();

    expect($sale->status)->toBe(SaleStatus::Open)
        ->and(Payment::query()->count())->toBe(0);

    $component
        ->call('completePaypalSale')
        ->assertSet('screenError', null)
        ->assertSet('lastPaymentMethod', 'paypal');

    $sale->refresh();
    $payment = Payment::query()->where('sale_id', $sale->id)->sole();

    expect($sale->status)->toBe(SaleStatus::Completed)
        ->and($payment->method)->toBe(PaymentMethod::Paypal)
        ->and($sale->cashSession->refresh()->cash_sales_cents)->toBe(0)
        ->and(AuditEvent::query()->where('event_key', 'payment.paypal.confirmed')->count())->toBe(1);
});
