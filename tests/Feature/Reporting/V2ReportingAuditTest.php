<?php

use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\SetProductConsumableAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Preparation\Enums\PreparationNotificationSound;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationStation;
use App\Modules\Preparation\Models\PreparationTask;
use App\Modules\Reporting\Queries\GetCashSessionReportQuery;
use App\Modules\Reporting\Queries\GetDailySummaryQuery;
use App\Modules\Reporting\Services\DailyReportCsvExporter;
use App\Modules\Sales\Actions\AddProductToSaleAction;
use App\Modules\Sales\Actions\ApplyManualDiscountToSaleItemAction;
use App\Modules\Sales\Actions\CompleteSaleAction;
use App\Modules\Sales\Actions\ReverseCompletedSaleAction;
use App\Modules\Sales\Actions\StartSaleAction;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Tickets\Actions\AssignTicketToHospitalityOrderAction;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Actions\RedeemTicketEntitlementAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

it('reports gross reversals net payments discounts and historical categories from one daily basis', function () {
    Carbon::setTestNow('2026-09-25 12:00:00');

    $cashier = app(CreateUserAction::class)->execute('v2-report-cashier', '123456', 'Casey', 'Cashier');
    $manager = app(CreateUserAction::class)->execute(
        'v2-report-manager',
        '123456',
        'Mara',
        'Manager',
        UserRole::Manager,
    );
    $register = Register::query()->create([
        'code' => 'v2-report-register',
        'name' => 'V2 Kasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 2000);
    $category = app(CreateCategoryAction::class)->execute('Historische Snacks');
    $cashProduct = app(CreateProductAction::class)->execute($category, 'Rabattartikel', 'Rabatt', 1000);
    $paypalProduct = app(CreateProductAction::class)->execute($category, 'Pfandchip', 'Chip', 500);
    app(SetProductConsumableAction::class)->execute($paypalProduct, false, $manager);

    $cashSale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $cashSale = app(AddProductToSaleAction::class)->execute($cashSale, $cashProduct);
    $cashItem = $cashSale->items()->sole();
    $cashSale = app(ApplyManualDiscountToSaleItemAction::class)->execute(
        $cashItem,
        $manager,
        DiscountType::FixedAmount,
        200,
    );
    app(CompleteSaleAction::class)->execute($cashSale, [[
        'method' => PaymentMethod::Cash,
        'amount_cents' => 800,
        'received_cents' => 1000,
        'change_cents' => 200,
        'confirmed_by_user_id' => $cashier->id,
    ]]);

    $paypalSale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $paypalSale = app(AddProductToSaleAction::class)->execute($paypalSale, $paypalProduct);
    $paypalSale = app(CompleteSaleAction::class)->execute($paypalSale, [[
        'method' => PaymentMethod::Paypal,
        'amount_cents' => 500,
        'received_cents' => 500,
        'change_cents' => 0,
        'confirmed_by_user_id' => $cashier->id,
        'provider_reference' => 'https://paypal.me/example/5.00EUR',
    ]]);

    app(ReverseCompletedSaleAction::class)->execute($paypalSale, $manager, 'V2 Reporting Test');

    $category->update(['name' => 'Später umbenannt']);

    $summary = app(GetDailySummaryQuery::class)->execute('2026-09-25');

    expect($summary['gross_sales_cents'])->toBe(1300)
        ->and($summary['reversal_cents'])->toBe(500)
        ->and($summary['net_revenue_cents'])->toBe(800)
        ->and($summary['cash_cents'])->toBe(800)
        ->and($summary['paypal_cents'])->toBe(500)
        ->and($summary['cash_refund_cents'])->toBe(0)
        ->and($summary['discount_cents'])->toBe(200)
        ->and($summary['manual_discount_cents'])->toBe(200)
        ->and($summary['products'][0]['category_name'])->toBe('Historische Snacks')
        ->and($summary['categories'][0]['category_name'])->toBe('Historische Snacks');

    $csv = app(DailyReportCsvExporter::class)->export(
        $summary,
        app(GetCashSessionReportQuery::class)->execute('2026-09-25'),
    );

    expect($csv)
        ->toContain('Buchhaltung;2026-09-25;;Bruttoverkauf')
        ->toContain('Buchhaltung;2026-09-25;;Storno')
        ->toContain('Buchhaltung;2026-09-25;;Netto-Umsatz')
        ->toContain('Zahlung;2026-09-25;;PayPal.me')
        ->toContain('Rabatt;2026-09-25;;Manuell')
        ->toContain('Historische Snacks')
        ->toContain('Kein DATEV-/Steuerexport');
});

it('reports hospitality tickets preparation and audit without counting ticket redemption as revenue', function () {
    Carbon::setTestNow('2026-09-25 14:00:00');

    $cashier = app(CreateUserAction::class)->execute('v2-ticket-cashier', '123456', 'Casey', 'Cashier');
    $waiter = app(CreateUserAction::class)->execute(
        'v2-ticket-waiter',
        '123456',
        'Wanda',
        'Waiter',
        UserRole::Waiter,
    );
    $register = Register::query()->create([
        'code' => 'v2-ticket-register',
        'name' => 'Ticketkasse',
        'active' => true,
    ]);
    $session = app(OpenCashSessionAction::class)->execute($register, $cashier, 0);
    $category = app(CreateCategoryAction::class)->execute('Menü');
    $product = app(CreateProductAction::class)->execute($category, 'Curry', 'Curry', 700);

    $sale = app(StartSaleAction::class)->execute($register, $session, $cashier);
    $sale = app(AddProductToSaleAction::class)->execute($sale, $product);
    $sale = app(CompleteSaleAction::class)->execute($sale, [[
        'method' => PaymentMethod::Cash,
        'amount_cents' => 700,
        'received_cents' => 700,
        'change_cents' => 0,
        'confirmed_by_user_id' => $cashier->id,
    ]]);

    $menu = app(CreateMenuAction::class)->execute('Ticketmenü', 700);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);

    $area = app(CreateDiningAreaAction::class)->execute('Terrasse');
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 9');
    $order = app(OpenHospitalityOrderAction::class)->execute($table, $waiter);

    $issued = app(IssueTicketAction::class)->execute(
        TicketFundingType::Paid,
        today(),
        [$menu->id => 1],
        $waiter,
        $sale,
    );
    $ticket = app(AssignTicketToHospitalityOrderAction::class)->execute(
        $issued['ticket'],
        $order,
        $waiter,
    );

    $redemption = app(RedeemTicketEntitlementAction::class)->execute(
        $ticket,
        $ticket->entitlements->sole(),
        $order,
        $waiter,
        [$group->id => [$product->id]],
    );

    $station = PreparationStation::query()->create([
        'name' => 'Küche',
        'code' => 'kueche-report',
        'active' => true,
        'sort_order' => 10,
        'notification_sound' => PreparationNotificationSound::Bell,
    ]);

    PreparationTask::query()->create([
        'station_id' => $station->id,
        'hospitality_order_id' => $order->id,
        'hospitality_order_item_id' => $redemption->hospitality_order_item_id,
        'hospitality_order_item_component_id' => null,
        'product_id' => $product->id,
        'source_key' => 'v2-report-task',
        'label_snapshot' => 'Curry',
        'component_label_snapshot' => null,
        'note_snapshot' => null,
        'quantity' => 1,
        'status' => PreparationTaskStatus::Ready,
        'started_by_user_id' => $waiter->id,
        'ready_by_user_id' => $waiter->id,
        'started_at' => now()->subMinutes(2),
        'ready_at' => now(),
    ]);

    $summary = app(GetDailySummaryQuery::class)->execute('2026-09-25');

    expect($summary['gross_sales_cents'])->toBe(700)
        ->and($summary['sales_count'])->toBe(1)
        ->and($summary['hospitality']['opened_orders'])->toBe(1)
        ->and($summary['hospitality']['waiters'][0]['name'])->toContain('Wanda')
        ->and($summary['hospitality']['areas'][0]['area_name'])->toBe('Terrasse')
        ->and($summary['hospitality']['tables'][0]['table_name'])->toBe('Tisch 9')
        ->and($summary['tickets']['paid'])->toBe(1)
        ->and($summary['tickets']['redemptions'])->toBe(1)
        ->and($summary['tickets']['redeemed_quantity'])->toBe(1)
        ->and($summary['tickets']['covered_value_cents'])->toBe(700)
        ->and($summary['preparation'][0]['station_name'])->toBe('Küche')
        ->and($summary['preparation'][0]['started_count'])->toBe(1)
        ->and($summary['preparation'][0]['ready_count'])->toBe(1)
        ->and($summary['preparation'][0]['average_seconds'])->toBe(120)
        ->and(collect($summary['audit'])->pluck('event_key'))->toContain('ticket.assigned')
        ->and(collect($summary['audit'])->pluck('event_key'))->toContain('ticket.redeemed');
});
