<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\SetProductConsumableAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Models\SaleReversal;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses the sale item snapshot when deciding whether a completed sale may be reversed', function () {
    $manager = app(CreateUserAction::class)->execute(
        'snapshot-reverse-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $cashier = app(CreateUserAction::class)->execute(
        'snapshot-reverse-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $register = Register::query()->create([
        'code' => 'snapshot-reverse-register',
        'name' => 'Hauptkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 1000);
    $category = app(CreateCategoryAction::class)->execute('Gemischt');

    $schoolBlock = app(CreateProductAction::class)->execute($category, 'Schulblock', 'Block', 200);
    app(SetProductConsumableAction::class)->execute($schoolBlock, false, $manager);

    $allowedSale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $allowedSale = app(AddProductToSaleAction::class)->execute($allowedSale, $schoolBlock);
    $allowedSale = app(CompleteCashSaleAction::class)->execute($allowedSale, 200);

    expect($allowedSale->items->sole()->is_consumable)->toBeFalse();

    app(SetProductConsumableAction::class)->execute($schoolBlock->refresh(), true, $manager);

    $allowedReversal = app(ReverseCompletedSaleAction::class)->execute(
        $allowedSale,
        $manager,
        'Nicht-Verzehrartikel zurückgegeben',
    );

    expect($allowedReversal->sale_id)->toBe($allowedSale->id);

    $drink = app(CreateProductAction::class)->execute($category, 'Apfelschorle', 'Schorle', 150);
    $pencil = app(CreateProductAction::class)->execute($category, 'Bleistift', 'Stift', 100);
    app(SetProductConsumableAction::class)->execute($pencil, false, $manager);

    $blockedSale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $blockedSale = app(AddProductToSaleAction::class)->execute($blockedSale, $drink);
    $blockedSale = app(AddProductToSaleAction::class)->execute($blockedSale, $pencil);
    $blockedSale = app(CompleteCashSaleAction::class)->execute($blockedSale, 250);

    expect($blockedSale->items->pluck('is_consumable')->sort()->values()->all())
        ->toBe([false, true]);

    app(SetProductConsumableAction::class)->execute($drink->refresh(), false, $manager);
    app(SetProductConsumableAction::class)->execute($pencil->refresh(), true, $manager);

    expect(fn () => app(ReverseCompletedSaleAction::class)->execute(
        $blockedSale,
        $manager,
        'Gemischten Verkauf vollständig stornieren',
    ))->toThrow(
        LogicException::class,
        'Verkäufe mit Lebensmitteln oder Getränken können in V2 nicht vollständig storniert werden.',
    );

    expect(SaleReversal::query()->where('sale_id', $blockedSale->id)->exists())->toBeFalse();
});
