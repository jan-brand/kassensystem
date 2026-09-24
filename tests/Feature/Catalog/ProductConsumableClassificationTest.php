<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Surfaces\Administration\Livewire\CatalogScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('treats new and legacy-compatible products as consumable until explicitly reclassified', function () {
    $manager = app(CreateUserAction::class)->execute(
        'consumable-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $category = app(CreateCategoryAction::class)->execute('Gemischt');
    $product = app(CreateProductAction::class)->execute($category, 'Schulblock', 'Block', 250);

    expect($product->is_consumable)->toBeTrue();

    $this->actingAs($manager);

    Livewire::test(CatalogScreen::class)
        ->call('toggleProductConsumable', $product->id)
        ->assertSee('Nicht-Verzehrartikel');

    expect(Product::query()->findOrFail($product->id)->is_consumable)->toBeFalse()
        ->and(
            AuditEvent::query()
                ->where('event_key', 'product.consumable_classification_changed')
                ->where('subject_id', $product->id)
                ->count(),
        )->toBe(1);
});
