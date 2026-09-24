<?php

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Tickets\Actions\AssignTicketToHospitalityOrderAction;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Queries\FindTicketByTokenQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('issues an unguessable token and stores only its hash', function () {
    $manager = app(CreateUserAction::class)->execute('ticket-manager', '123456', 'Mara', 'Manager', UserRole::Manager);
    $category = app(CreateCategoryAction::class)->execute('Ticketgerichte');
    $product = app(CreateProductAction::class)->execute($category, 'Pasta', 'Pasta', 500);
    $menu = app(CreateMenuAction::class)->execute('Tagesmenü', 600);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);

    $result = app(IssueTicketAction::class)->execute(
        TicketFundingType::Free,
        today(),
        [$menu->id => 1],
        $manager,
    );

    expect($result['token'])->toMatch('/^[a-f0-9]{64}$/')
        ->and($result['ticket']->token_hash)->toBe(hash('sha256', $result['token']))
        ->and($result['ticket']->token_hash)->not->toBe($result['token'])
        ->and(app(FindTicketByTokenQuery::class)->execute($result['token'])?->id)->toBe($result['ticket']->id);
});

it('assigns a ticket once and never moves it to another table', function () {
    $waiter = app(CreateUserAction::class)->execute('ticket-waiter', '123456', 'Wanda', 'Waiter', UserRole::Waiter);
    $category = app(CreateCategoryAction::class)->execute('Ticketgerichte');
    $product = app(CreateProductAction::class)->execute($category, 'Curry', 'Curry', 550);
    $menu = app(CreateMenuAction::class)->execute('Mittagsmenü', 700);
    $group = app(CreateMenuGroupAction::class)->execute($menu, 'Hauptgericht', 1, 1);
    app(AddProductToMenuGroupAction::class)->execute($group, $product);
    $area = app(CreateDiningAreaAction::class)->execute('Mensa');
    $firstTable = app(CreateDiningTableAction::class)->execute($area, 'Tisch 1');
    $secondTable = app(CreateDiningTableAction::class)->execute($area, 'Tisch 2');
    $firstOrder = app(OpenHospitalityOrderAction::class)->execute($firstTable, $waiter);
    $secondOrder = app(OpenHospitalityOrderAction::class)->execute($secondTable, $waiter);
    $ticket = app(IssueTicketAction::class)->execute(TicketFundingType::Free, today(), [$menu->id => 1], $waiter)['ticket'];

    $assigned = app(AssignTicketToHospitalityOrderAction::class)->execute($ticket, $firstOrder, $waiter);

    expect($assigned->status)->toBe(TicketStatus::Assigned)
        ->and($assigned->assigned_order_id)->toBe($firstOrder->id)
        ->and($assigned->assigned_table_id)->toBe($firstTable->id);

    expect(fn () => app(AssignTicketToHospitalityOrderAction::class)->execute($ticket, $secondOrder, $waiter))
        ->toThrow(LogicException::class, 'anderen Tisch');

    $secondTicket = app(IssueTicketAction::class)->execute(TicketFundingType::Free, today(), [$menu->id => 1], $waiter)['ticket'];
    app(AssignTicketToHospitalityOrderAction::class)->execute($secondTicket, $firstOrder, $waiter);

    expect(Ticket::query()->findOrFail($ticket->id)->assigned_order_id)->toBe($firstOrder->id)
        ->and(Ticket::query()->where('assigned_order_id', $firstOrder->id)->count())->toBe(2);
});
