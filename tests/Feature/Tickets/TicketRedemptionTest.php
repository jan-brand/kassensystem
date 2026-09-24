<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CloseHospitalityOrderAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tickets\Actions\AssignTicketToHospitalityOrderAction;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Actions\RedeemTicketEntitlementAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Models\TicketRedemption;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prevents over redemption and keeps a free redemption revenue neutral', function () {
    $waiter = app(CreateUserAction::class)->execute('free-ticket-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Gratis-Menü');
    $product = app(CreateProductAction::class)->execute($category, 'Pasta', 'Pasta', 500);
    $menu = app(CreateMenuAction::class)->execute('Freimenü', 650);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $area = app(CreateDiningAreaAction::class)->execute('Mensa');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch Frei');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    $ticket = app(IssueTicketAction::class)->execute(TicketFundingType::Free, today(), [$menu->id => 1], $waiter)['ticket'];
    $ticket = app(AssignTicketToHospitalityOrderAction::class)->execute($ticket, $order, $waiter);
    $entitlement = $ticket->entitlements->sole();

    expect(fn () => app(RedeemTicketEntitlementAction::class)->execute(
        $ticket,
        $entitlement,
        $order,
        $waiter,
        [$group->id => [$product->id]],
        2,
    ))->toThrow(LogicException::class, 'reicht');

    $salesBefore = Sale::query()->count();
    $paymentsBefore = Payment::query()->count();

    $redemption = app(RedeemTicketEntitlementAction::class)->execute(
        $ticket,
        $entitlement,
        $order,
        $waiter,
        [$group->id => [$product->id]],
    );

    expect($redemption->covered_value_cents)->toBe(650)
        ->and($order->refresh()->total_cents)->toBe(0)
        ->and($order->items()->sole()->total_cents)->toBe(0)
        ->and(Sale::query()->count())->toBe($salesBefore)
        ->and(Payment::query()->count())->toBe($paymentsBefore)
        ->and(TicketRedemption::query()->count())->toBe(1);
});

it('links paid tickets to an existing completed sale without counting redemption twice', function () {
    $cashier = app(CreateUserAction::class)->execute('paid-ticket-cashier', '123456', 'Casey', 'Cashier');
    $waiter = app(CreateUserAction::class)->execute('paid-ticket-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $register = Register::query()->create(['code' => 'ticket-register', 'name' => 'Ticketkasse', 'active' => true]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Bezahltes Menü');
    $product = app(CreateProductAction::class)->execute($category, 'Curry', 'Curry', 700);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteCashSaleAction::class)->execute($sale, 1000);

    $menu = app(CreateMenuAction::class)->execute('Ticketmenü', 700);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $area = app(CreateDiningAreaAction::class)->execute('Terrasse');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch Bezahlt');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);

    $issued = app(IssueTicketAction::class)->execute(TicketFundingType::Paid, today(), [$menu->id => 1], $waiter, $sale);
    $ticket = app(AssignTicketToHospitalityOrderAction::class)->execute($issued['ticket'], $order, $waiter);
    $salesBefore = Sale::query()->count();
    $paymentsBefore = Payment::query()->count();
    $cashSalesBefore = $session->refresh()->cash_sales_cents;

    app(RedeemTicketEntitlementAction::class)->execute(
        $ticket,
        $ticket->entitlements->sole(),
        $order,
        $waiter,
        [$group->id => [$product->id]],
    );

    expect($ticket->sale_id)->toBe($sale->id)
        ->and(Sale::query()->count())->toBe($salesBefore)
        ->and(Payment::query()->count())->toBe($paymentsBefore)
        ->and($session->refresh()->cash_sales_cents)->toBe($cashSalesBefore)
        ->and($order->refresh()->total_cents)->toBe(0);
});

it('stops ticket usage after the assigned table is closed', function () {
    $waiter = app(CreateUserAction::class)->execute('closed-ticket-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Schluss-Menü');
    $product = app(CreateProductAction::class)->execute($category, 'Suppe', 'Suppe', 400);
    $menu = app(CreateMenuAction::class)->execute('Schlussmenü', 450);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $area = app(CreateDiningAreaAction::class)->execute('Innen');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch Schluss');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);
    $ticket = app(IssueTicketAction::class)->execute(TicketFundingType::Free, today(), [$menu->id => 1], $waiter)['ticket'];
    $ticket = app(AssignTicketToHospitalityOrderAction::class)->execute($ticket, $order, $waiter);
    app(CloseHospitalityOrderAction::class)->execute($order, $waiter);

    expect(fn () => app(RedeemTicketEntitlementAction::class)->execute(
        $ticket,
        $ticket->entitlements->sole(),
        $order,
        $waiter,
        [$group->id => [$product->id]],
    ))->toThrow(LogicException::class, 'Tischschluss');
});
