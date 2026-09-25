<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\SetProductConsumableAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\DigitalReceiptKind;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\DigitalReceipt;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Services\DigitalReceiptService;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('publishes an opaque receipt token from immutable sale snapshots without personal data', function () {
    [$sale, $product] = digitalReceiptSaleFixture();

    $receipts = app(DigitalReceiptService::class);
    $receipt = $receipts->ensureSaleReceipt($sale);
    $url = $receipts->publicUrl($receipt);
    $token = basename((string) parse_url($url, PHP_URL_PATH));

    expect($receipt->kind)->toBe(DigitalReceiptKind::Sale)
        ->and($token)->toHaveLength(48)
        ->and($token)->not->toBe((string) $sale->id)
        ->and($token)->not->toBe($sale->number)
        ->and($receipt->token_hash)->toBe(hash('sha256', $token))
        ->and($receipt->token_ciphertext)->not->toContain($token);

    $product->update([
        'name' => 'Später umbenannt',
        'price_cents' => 999,
    ]);

    $this->get($url)
        ->assertOk()
        ->assertSee('Brezel')
        ->assertDontSee('Später umbenannt')
        ->assertDontSee('Casey Cashier');
});

it('stops public access after retention expires and leaves the completed sale unchanged', function () {
    $admin = app(CreateUserAction::class)->execute(
        'receipt-settings-admin',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    app(UpdateSystemSettingsAction::class)->execute(
        actor: $admin,
        cafeteriaName: 'Schulcafeteria',
        receiptRetentionDays: 1,
    );

    [$sale] = digitalReceiptSaleFixture('receipt-expiry');
    $receipts = app(DigitalReceiptService::class);
    $receipt = $receipts->ensureSaleReceipt($sale);
    $url = $receipts->publicUrl($receipt);
    $saleId = $sale->id;
    $total = $sale->total_cents;

    $this->travelTo($receipt->expires_at->copy()->addSecond());

    $this->get($url)->assertNotFound();
    $this->get(route('sales.receipt.public', ['token' => str_repeat('A', 48)]))->assertNotFound();

    $this->travelBack();

    $unchanged = $sale->refresh();

    expect($unchanged->id)->toBe($saleId)
        ->and($unchanged->status)->toBe(SaleStatus::Completed)
        ->and($unchanged->total_cents)->toBe($total);
});

it('creates a separate public reversal receipt without exposing the internal reason or actor', function () {
    [$sale] = digitalReceiptSaleFixture('receipt-reversal', false);
    $manager = app(CreateUserAction::class)->execute(
        'receipt-reversal-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );

    $reason = 'Interner Grund mit vertraulichem Hinweis';
    $reversal = app(ReverseCompletedSaleAction::class)->execute($sale, $manager, $reason);
    $receipts = app(DigitalReceiptService::class);
    $saleReceipt = $receipts->ensureSaleReceipt($sale);
    $reversalReceipt = $receipts->ensureReversalReceipt($reversal);
    $saleUrl = $receipts->publicUrl($saleReceipt);
    $reversalUrl = $receipts->publicUrl($reversalReceipt);

    expect($saleUrl)->not->toBe($reversalUrl)
        ->and($reversalReceipt->kind)->toBe(DigitalReceiptKind::Reversal)
        ->and(DigitalReceipt::query()->where('sale_id', $sale->id)->count())->toBe(2);

    $this->get($reversalUrl)
        ->assertOk()
        ->assertSee('Stornobeleg')
        ->assertSee('Gegenbuchung')
        ->assertDontSee($reason)
        ->assertDontSee('Mara Manager');
});

/**
 * @return array{0: Sale, 1: Product}
 */
function digitalReceiptSaleFixture(string $suffix = 'receipt-base', bool $consumable = true): array
{
    $cashier = app(CreateUserAction::class)->execute(
        $suffix.'-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => $suffix.'-register',
        'name' => 'Belegkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Beleg '.$suffix);
    $product = app(CreateProductAction::class)->execute($category, 'Brezel', 'Brezel', 250);

    if (! $consumable) {
        app(SetProductConsumableAction::class)->execute($product, false);
    }

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteCashSaleAction::class)->execute($sale, 500);

    return [$sale, $product];
}
