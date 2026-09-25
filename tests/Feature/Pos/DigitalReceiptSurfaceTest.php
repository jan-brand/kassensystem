<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Surfaces\Pos\Livewire\RegisterScreen;
use App\Surfaces\Waiter\Livewire\TicketRedemptionScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows a digital receipt qr after completing a pos sale', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'receipt-pos-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $category = app(CreateCategoryAction::class)->execute('Beleg POS');
    $product = app(CreateProductAction::class)->execute($category, 'Wasser', 'Wasser', 150);

    $this->actingAs($cashier);

    Livewire::test(RegisterScreen::class)
        ->call('openCashSession')
        ->call('addProduct', $product->id)
        ->call('showPayment')
        ->set('receivedAmount', '2,00')
        ->call('completeSale')
        ->assertSet('screenError', null)
        ->assertSeeHtml('data-testid="sale-receipt-qr"')
        ->assertSee('Beleg öffnen');

    expect(Sale::query()->sole()->number)->not->toBeNull();
});

it('shows the paid ticket sale receipt qr on the waiter ticket surface', function () {
    $cashier = app(CreateUserAction::class)->execute(
        'receipt-ticket-cashier',
        '123456',
        'Casey',
        'Cashier',
    );
    $waiter = app(CreateUserAction::class)->execute(
        'receipt-ticket-waiter',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );
    $register = Register::query()->create([
        'code' => 'receipt-ticket-register',
        'name' => 'Ticketkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Beleg Ticket');
    $product = app(CreateProductAction::class)->execute($category, 'Curry', 'Curry', 700);
    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteCashSaleAction::class)->execute($sale, 1000);

    $menu = app(CreateMenuAction::class)->execute('Belegmenü', 700);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $issued = app(IssueTicketAction::class)->execute(
        TicketFundingType::Paid,
        today(),
        [$menu->id => 1],
        $waiter,
        $sale,
    );

    $this->actingAs($waiter);

    Livewire::test(TicketRedemptionScreen::class)
        ->set('token', $issued['token'])
        ->call('scan')
        ->assertSet('ticketId', $issued['ticket']->id)
        ->assertSeeHtml('data-testid="ticket-sale-receipt-qr"')
        ->assertSee('Digitaler Kaufbeleg');
});
