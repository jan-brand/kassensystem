<?php

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\CashierManagementService;
use App\Surfaces\Administration\Livewire\CashiersScreen;
use App\Surfaces\Administration\Livewire\CatalogScreen;
use App\Surfaces\Administration\Livewire\ReportingScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('protects administration routes by role', function () {
    expect(Route::has('administration.dashboard'))->toBeTrue();

    $this->get(route('administration.dashboard'))->assertRedirect(route('login'));

    $cashier = app(CreateUserAction::class)->execute('cashier-admin-test', '123456', 'Casey', 'Cashier');
    $this->actingAs($cashier)->get(route('administration.dashboard'))->assertForbidden();

    $manager = app(CreateUserAction::class)->execute(
        'manager-admin-test',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager)->get(route('administration.dashboard'))->assertOk();
});

it('manages categories and products', function () {
    $manager = app(CreateUserAction::class)->execute(
        'manager-catalog',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager);

    $component = Livewire::test(CatalogScreen::class)
        ->set('categoryName', 'Getränke')
        ->set('categorySortOrder', 10)
        ->call('createCategory');

    $category = Category::query()->firstOrFail();

    $component
        ->set('productCategoryId', $category->id)
        ->set('productName', 'Apfelschorle')
        ->set('productShortName', 'Schorle')
        ->set('productPrice', '1,50')
        ->call('createProduct');

    $product = Product::query()->firstOrFail();

    expect($product->price_cents)->toBe(150);

    $component
        ->call('startEditProduct', $product->id)
        ->set('editProductPrice', '1,80')
        ->call('saveProduct')
        ->call('toggleProduct', $product->id);

    $product->refresh();

    expect($product->price_cents)->toBe(180)
        ->and($product->active)->toBeFalse();
});

it('lets managers manage cashier accounts but not administrators', function () {
    $manager = app(CreateUserAction::class)->execute(
        'manager-users',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $administrator = app(CreateUserAction::class)->execute(
        'administrator-users',
        '123456',
        'Ada',
        'Admin',
        UserRole::Administrator,
    );
    $this->actingAs($manager);

    Livewire::test(CashiersScreen::class)
        ->set('username', 'kasse-neu')
        ->set('pin', '654321')
        ->set('firstName', 'Klara')
        ->set('lastName', 'Kasse')
        ->call('createCashier');

    $cashier = User::query()->where('username', 'kasse-neu')->firstOrFail();

    $component = Livewire::test(CashiersScreen::class)
        ->call('startEdit', $cashier->id)
        ->set('editDisplayName', 'Klara K.')
        ->call('saveCashier')
        ->call('startPinReset', $cashier->id)
        ->set('newPin', '111222')
        ->call('resetPin')
        ->call('toggleActive', $cashier->id);

    $cashier->refresh();

    expect($cashier->display_name)->toBe('Klara K.')
        ->and(Hash::check('111222', $cashier->pin_hash))->toBeTrue()
        ->and($cashier->active)->toBeFalse();

    expect(fn () => app(CashierManagementService::class)->setActive($manager, $administrator, false))
        ->toThrow(LogicException::class, 'This area may only manage cashier accounts.');
});

it('renders the reporting page', function () {
    $manager = app(CreateUserAction::class)->execute(
        'manager-reporting',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $this->actingAs($manager);

    Livewire::test(ReportingScreen::class)
        ->assertStatus(200)
        ->assertSee('Berichte');
});
