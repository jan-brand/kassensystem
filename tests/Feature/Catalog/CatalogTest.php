<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\SetProductActiveAction;
use App\Modules\Catalog\Queries\GetPosCatalogQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows zero price products but rejects negative prices', function () {
    $category = app(CreateCategoryAction::class)->execute('Getränke');

    $free = app(CreateProductAction::class)->execute(
        category: $category,
        name: 'Wasser kostenlos',
        shortName: 'Wasser',
        priceCents: 0,
    );

    expect($free->price_cents)->toBe(0);

    expect(fn () => app(CreateProductAction::class)->execute(
        category: $category,
        name: 'Ungültig',
        shortName: 'Ungültig',
        priceCents: -1,
    ))->toThrow(InvalidArgumentException::class);
});

it('returns only active categories and products for the pos', function () {
    $category = app(CreateCategoryAction::class)->execute('Snacks');

    $active = app(CreateProductAction::class)->execute(
        $category,
        'Brezel',
        'Brezel',
        100,
    );

    $inactive = app(CreateProductAction::class)->execute(
        $category,
        'Alter Snack',
        'Alt',
        50,
    );

    app(SetProductActiveAction::class)->execute($inactive, false);

    $catalog = app(GetPosCatalogQuery::class)->execute();

    expect($catalog)->toHaveCount(1)
        ->and($catalog->first()->products)->toHaveCount(1)
        ->and($catalog->first()->products->first()->id)->toBe($active->id);
});
