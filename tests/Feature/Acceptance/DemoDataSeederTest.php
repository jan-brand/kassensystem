<?php

use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Settings\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds a complete local demo dataset with closed sample history', function () {
    $this->artisan('app:demo:seed')
        ->assertSuccessful();

    expect(User::query()->count())->toBe(3)
        ->and(User::query()->where('role', UserRole::Administrator->value)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::Manager->value)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::Cashier->value)->count())->toBe(1)
        ->and(Category::query()->count())->toBe(5)
        ->and(Product::query()->count())->toBe(14)
        ->and(Register::query()->where('active', true)->count())->toBe(1)
        ->and(SystemSetting::query()->find(1)?->cafeteria_name)->toBe('Demo Cafeteria');

    $cashier = User::query()->where('username', 'demo-kasse')->firstOrFail();

    expect(Hash::check('910003', $cashier->pin_hash))->toBeTrue()
        ->and(Sale::query()->where('status', SaleStatus::Completed->value)->count())->toBe(3)
        ->and(Payment::query()->count())->toBe(2)
        ->and(CashSession::query()->where('status', CashSessionStatus::Closed->value)->count())->toBe(1)
        ->and(CashSession::query()->whereIn('status', [
            CashSessionStatus::Open->value,
            CashSessionStatus::Closing->value,
        ])->count())->toBe(0);

    $summary = app(GetDailySummaryQuery::class)->execute(
        now((string) config('kassensystem.timezone', 'Europe/Berlin'))->format('Y-m-d'),
    );

    expect($summary['sales_count'])->toBe(3)
        ->and($summary['revenue_cents'])->toBe(800)
        ->and($summary['free_sales_count'])->toBe(1);
});

it('is idempotent for demo master data and sample history', function () {
    $this->artisan('app:demo:seed')->assertSuccessful();
    $this->artisan('app:demo:seed')->assertSuccessful();

    expect(User::query()->count())->toBe(3)
        ->and(Category::query()->count())->toBe(5)
        ->and(Product::query()->count())->toBe(14)
        ->and(Register::query()->count())->toBe(1)
        ->and(SystemSetting::query()->count())->toBe(1)
        ->and(Sale::query()->where('status', SaleStatus::Completed->value)->count())->toBe(3)
        ->and(CashSession::query()->count())->toBe(1);
});

it('can seed demo master data without creating transaction history', function () {
    $this->artisan('app:demo:seed', ['--no-history' => true])
        ->assertSuccessful();

    expect(User::query()->count())->toBe(3)
        ->and(Category::query()->count())->toBe(5)
        ->and(Product::query()->count())->toBe(14)
        ->and(Register::query()->count())->toBe(1)
        ->and(Sale::query()->count())->toBe(0)
        ->and(CashSession::query()->count())->toBe(0);
});
