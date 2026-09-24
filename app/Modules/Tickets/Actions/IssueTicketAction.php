<?php

namespace App\Modules\Tickets\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Enums\TicketStatus;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketEntitlement;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class IssueTicketAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    /**
     * @param  array<int, int>  $menuQuantities  menu id => allowed quantity
     * @return array{ticket: Ticket, token: string}
     */
    public function execute(
        TicketFundingType $fundingType,
        CarbonInterface $validOn,
        array $menuQuantities,
        User $actor,
        ?Sale $sale = null,
    ): array {
        if ($validOn->toDateString() < today()->toDateString()) {
            throw new InvalidArgumentException('Tickets können nicht für vergangene Tage ausgegeben werden.');
        }

        if ($menuQuantities === []) {
            throw new InvalidArgumentException('Mindestens eine Menüberechtigung ist erforderlich.');
        }

        if ($fundingType === TicketFundingType::Free && $sale instanceof Sale) {
            throw new InvalidArgumentException('Kostenlose Tickets dürfen keinen Verkauf referenzieren.');
        }

        if ($fundingType === TicketFundingType::Paid && ! $sale instanceof Sale) {
            throw new InvalidArgumentException('Bezahlte Tickets benötigen einen abgeschlossenen Verkauf.');
        }

        if ($sale instanceof Sale) {
            $sale = Sale::query()->with('payment')->findOrFail($sale->id);

            if ($sale->status !== SaleStatus::Completed || $sale->number === null || $sale->payment === null) {
                throw new LogicException('Bezahlte Tickets dürfen nur einen abgeschlossenen Verkauf mit Zahlung referenzieren.');
            }
        }

        $normalized = [];
        foreach ($menuQuantities as $menuId => $quantity) {
            $menuId = (int) $menuId;
            $quantity = (int) $quantity;

            if ($menuId < 1 || $quantity < 1 || $quantity > 999) {
                throw new InvalidArgumentException('Ticket-Mengen müssen zwischen 1 und 999 liegen.');
            }

            $normalized[$menuId] = ($normalized[$menuId] ?? 0) + $quantity;
        }

        return DB::transaction(function () use ($fundingType, $validOn, $normalized, $actor, $sale): array {
            $menus = Menu::query()
                ->whereIn('id', array_keys($normalized))
                ->where('active', true)
                ->get()
                ->keyBy('id');

            if ($menus->count() !== count($normalized)) {
                throw new LogicException('Mindestens ein ausgewähltes Menü ist nicht verfügbar.');
            }

            $plainToken = bin2hex(random_bytes(32));

            $ticket = Ticket::query()->create([
                'token_hash' => hash('sha256', $plainToken),
                'status' => TicketStatus::Issued,
                'funding_type' => $fundingType,
                'valid_on' => $validOn->toDateString(),
                'sale_id' => $sale?->id,
                'issued_by_user_id' => $actor->id,
                'assigned_order_id' => null,
                'assigned_table_id' => null,
                'assigned_at' => null,
                'redeemed_at' => null,
            ]);

            foreach ($normalized as $menuId => $quantity) {
                $menu = $menus->get($menuId);

                if (! $menu instanceof Menu) {
                    throw new LogicException('Ausgewähltes Menü wurde nicht gefunden.');
                }

                TicketEntitlement::query()->create([
                    'ticket_id' => $ticket->id,
                    'menu_id' => $menuId,
                    'menu_name_snapshot' => $menu->name,
                    'quantity_allowed' => $quantity,
                    'quantity_redeemed' => 0,
                ]);
            }

            $this->audit->execute(
                eventKey: 'ticket.issued',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: Ticket::class,
                subjectId: $ticket->id,
                after: [
                    'funding_type' => $fundingType->value,
                    'valid_on' => $validOn->toDateString(),
                    'sale_id' => $sale?->id,
                    'entitlements' => $normalized,
                ],
            );

            return [
                'ticket' => $ticket->load(['entitlements.menu', 'sale.payment']),
                'token' => $plainToken,
            ];
        });
    }
}
