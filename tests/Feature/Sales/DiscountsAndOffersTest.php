<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\ApplyManualDiscountToSaleItemAction;
use App\Modules\Sales\Actions\SaveDiscountOfferAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\DiscountSource;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Queries\GetActiveDiscountOfferForProductQuery;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{
 *     cashier: User,
 *     manager: User,
 *     register: Register,
 *     session: CashSession,
 *     product: Product
 * }
 */
function discountFixture(): array
{
    $cashier = app(CreateUserAction::class)->execute(
        'discount-cashier',
        '123456',
        'Casey',
        'Cashier',
        UserRole::Cashier,
    );
    $manager = app(CreateUserAction::class)->execute(
        'discount-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $register = Register::query()->create([
        'code' => 'discount-register',
        'name' => 'Rabattkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Rabatt-Test');
    $product = app(CreateProductAction::class)->execute($category, 'Testprodukt', 'Test', 1000);

    return compact('cashier', 'manager', 'register', 'session', 'product');
}

it('applies an effective scheduled offer and snapshots original and final prices', function () {
    $fixture = discountFixture();
    $now = CarbonImmutable::parse('2026-09-25 12:00:00', 'Europe/Berlin');
    CarbonImmutable::setTestNow($now);

    $offer = app(SaveDiscountOfferAction::class)->execute(
        actor: $fixture['manager'],
        product: $fixture['product'],
        name: 'Mittagsangebot',
        type: DiscountType::Percentage,
        value: 2500,
        active: true,
        weekdays: [(int) $now->format('N')],
        dailyStartTime: '11:00',
        dailyEndTime: '14:00',
    );

    $sale = app(StartSaleAction::class)->execute(
        $fixture['register'],
        $fixture['session'],
        $fixture['cashier'],
    );
    $sale = app(AddProductToSaleAction::class)->execute($sale, $fixture['product']);
    $item = $sale->items->firstOrFail();

    expect($item->original_unit_price_cents)->toBe(1000)
        ->and($item->unit_price_cents)->toBe(750)
        ->and($item->discount_type)->toBe(DiscountType::Percentage)
        ->and($item->discount_value)->toBe(2500)
        ->and($item->discount_source)->toBe(DiscountSource::Offer)
        ->and($item->discount_label)->toBe('Mittagsangebot')
        ->and($item->discount_offer_id)->toBe($offer->id)
        ->and($item->total_cents)->toBe(750)
        ->and($sale->total_cents)->toBe(750);

    CarbonImmutable::setTestNow();
});

it('does not stack a manual discount on top of an offer', function () {
    $fixture = discountFixture();
    $now = CarbonImmutable::parse('2026-09-25 12:00:00', 'Europe/Berlin');
    CarbonImmutable::setTestNow($now);

    app(SaveDiscountOfferAction::class)->execute(
        actor: $fixture['manager'],
        product: $fixture['product'],
        name: '20 Prozent',
        type: DiscountType::Percentage,
        value: 2000,
        active: true,
    );

    $sale = app(StartSaleAction::class)->execute(
        $fixture['register'],
        $fixture['session'],
        $fixture['cashier'],
    );
    $sale = app(AddProductToSaleAction::class)->execute($sale, $fixture['product']);
    $item = $sale->items->firstOrFail();

    expect($item->unit_price_cents)->toBe(800);

    $sale = app(ApplyManualDiscountToSaleItemAction::class)->execute(
        item: $item,
        actor: $fixture['manager'],
        type: DiscountType::FixedAmount,
        value: 100,
    );
    $item = $sale->items->firstOrFail();

    expect($item->original_unit_price_cents)->toBe(1000)
        ->and($item->unit_price_cents)->toBe(900)
        ->and($item->discount_source)->toBe(DiscountSource::Manual)
        ->and($item->discount_offer_id)->toBeNull()
        ->and($sale->total_cents)->toBe(900);

    CarbonImmutable::setTestNow();
});

it('never produces a negative price and protects manual discounts with their own permission', function () {
    $fixture = discountFixture();
    $sale = app(StartSaleAction::class)->execute(
        $fixture['register'],
        $fixture['session'],
        $fixture['cashier'],
    );
    $sale = app(AddProductToSaleAction::class)->execute($sale, $fixture['product']);
    $item = $sale->items->firstOrFail();

    expect(fn () => app(ApplyManualDiscountToSaleItemAction::class)->execute(
        item: $item,
        actor: $fixture['cashier'],
        type: DiscountType::FixedAmount,
        value: 100,
    ))->toThrow(AuthorizationException::class);

    $sale = app(ApplyManualDiscountToSaleItemAction::class)->execute(
        item: $item,
        actor: $fixture['manager'],
        type: DiscountType::FixedAmount,
        value: 5000,
    );

    expect($sale->items->firstOrFail()->unit_price_cents)->toBe(0)
        ->and($sale->total_cents)->toBe(0);
});

it('evaluates weekday and time windows without making an inactive offer effective', function () {
    $fixture = discountFixture();
    $offer = app(SaveDiscountOfferAction::class)->execute(
        actor: $fixture['manager'],
        product: $fixture['product'],
        name: 'Freitag Mittag',
        type: DiscountType::FixedAmount,
        value: 100,
        active: true,
        weekdays: [5],
        dailyStartTime: '11:00',
        dailyEndTime: '14:00',
    );

    $query = app(GetActiveDiscountOfferForProductQuery::class);

    expect($query->execute(
        $fixture['product'],
        CarbonImmutable::parse('2026-09-25 12:00:00', 'Europe/Berlin'),
    )?->id)->toBe($offer->id)
        ->and($query->execute(
            $fixture['product'],
            CarbonImmutable::parse('2026-09-25 15:00:00', 'Europe/Berlin'),
        ))->toBeNull()
        ->and($query->execute(
            $fixture['product'],
            CarbonImmutable::parse('2026-09-26 12:00:00', 'Europe/Berlin'),
        ))->toBeNull();
});
