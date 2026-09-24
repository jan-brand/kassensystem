<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Hospitality\Models\MenuGroup;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Surfaces\Administration\Livewire\MenuConfigurationScreen;
use App\Surfaces\Administration\Livewire\TicketsScreen;
use App\Surfaces\Waiter\Livewire\TicketRedemptionScreen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('protects ticket and menu administration and exposes redemption to waiters', function () {
    $cashier = app(CreateUserAction::class)->execute('ticket-route-cashier', '123456', 'Casey', 'Cashier');
    $waiter = app(CreateUserAction::class)->execute('ticket-route-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $manager = app(CreateUserAction::class)->execute('ticket-route-manager', '123456', 'Mara', 'Manager', UserRole::Manager);

    $this->actingAs($cashier)->get(route('waiter.tickets'))->assertForbidden();
    $this->actingAs($waiter)->get(route('waiter.tickets'))->assertOk();
    $this->actingAs($waiter)->get(route('administration.tickets'))->assertForbidden();
    $this->actingAs($waiter)->get(route('administration.menus'))->assertForbidden();
    $this->actingAs($manager)->get(route('administration.tickets'))->assertOk();
    $this->actingAs($manager)->get(route('administration.menus'))->assertOk();
});

it('lets a manager configure a hospitality menu that becomes selectable for tickets', function () {
    $manager = app(CreateUserAction::class)->execute('ticket-menu-manager', '123456', 'Mara', 'Manager', UserRole::Manager);
    $category = app(CreateCategoryAction::class)->execute('Ticket-Menü-Katalog');
    $product = app(CreateProductAction::class)->execute($category, 'Pasta', 'Pasta', 500);

    $this->actingAs($manager);

    $component = Livewire::test(MenuConfigurationScreen::class)
        ->set('menuName', 'Campusmenü')
        ->set('menuPrice', '6,50')
        ->call('createMenu')
        ->assertSet('screenError', null);

    $menu = Menu::query()->sole();

    $component
        ->set('groupMenuId', $menu->id)
        ->set('groupName', 'Hauptgericht')
        ->set('groupMinChoices', 1)
        ->set('groupMaxChoices', 1)
        ->call('createGroup')
        ->assertSet('screenError', null);

    $group = MenuGroup::query()->sole();

    $component
        ->set('productGroupId', $group->id)
        ->set('productId', $product->id)
        ->set('productPriceDelta', '0,00')
        ->call('addProduct')
        ->assertSet('screenError', null);

    Livewire::test(TicketsScreen::class)
        ->assertSee('Campusmenü')
        ->assertDontSee('Noch keine aktiven Menüs');
});

it('lets a waiter scan assign and redeem a ticket from the waiter surface', function () {
    $waiter = app(CreateUserAction::class)->execute('ticket-ui-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('UI-Menü');
    $product = app(CreateProductAction::class)->execute($category, 'Nudeln', 'Nudeln', 500);
    $menu = app(CreateMenuAction::class)->execute('UI-Tagesmenü', 600);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $area = app(CreateDiningAreaAction::class)->execute('UI-Bereich');
    $table = app(CreateDiningTableAction::class)->execute($area, 'UI-Tisch');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    $issued = app(IssueTicketAction::class)->execute(TicketFundingType::Free, today(), [$menu->id => 1], $waiter);
    $entitlement = $issued['ticket']->entitlements->sole();

    $this->actingAs($waiter);

    Livewire::test(TicketRedemptionScreen::class)
        ->set('token', $issued['token'])
        ->call('scan')
        ->assertSet('ticketId', $issued['ticket']->id)
        ->call('selectOrder', $order->id)
        ->call('assign')
        ->assertSet('selectedOrderId', $order->id)
        ->call('chooseEntitlement', $entitlement->id)
        ->set("menuSelections.{$group->id}", [$product->id])
        ->call('redeem')
        ->assertHasNoErrors();

    expect($order->refresh()->items()->count())->toBe(1)
        ->and($order->total_cents)->toBe(0);
});
