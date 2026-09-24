<?php

namespace App\Surfaces\Waiter\Livewire;

use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Tickets\Actions\AssignTicketToHospitalityOrderAction;
use App\Modules\Tickets\Actions\RedeemTicketEntitlementAction;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketEntitlement;
use App\Modules\Tickets\Queries\FindTicketByTokenQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.waiter')]
final class TicketRedemptionScreen extends Component
{
    public string $token = '';

    public ?int $ticketId = null;

    public ?int $selectedOrderId = null;

    public ?int $selectedEntitlementId = null;

    public int $redemptionQuantity = 1;

    /** @var array<int|string, list<int|string>> */
    public array $menuSelections = [];

    public string $note = '';

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::TicketsRedeem->value);
    }

    public function scan(FindTicketByTokenQuery $query): void
    {
        $this->clearMessages();
        $ticket = $query->execute($this->token);

        if (! $ticket instanceof Ticket) {
            $this->screenError = 'Ticket wurde nicht gefunden.';
            $this->ticketId = null;

            return;
        }

        $this->ticketId = $ticket->id;
        $this->selectedOrderId = $ticket->assigned_order_id;
        $this->selectedEntitlementId = null;
        $this->menuSelections = [];
        $this->redemptionQuantity = 1;
        $this->note = '';
    }

    public function selectOrder(int $orderId): void
    {
        $this->clearMessages();
        $ticket = $this->requireTicket();
        $order = HospitalityOrder::query()
            ->where('status', HospitalityOrderStatus::Open->value)
            ->findOrFail($orderId);

        if ($ticket->assigned_order_id !== null && $ticket->assigned_order_id !== $order->id) {
            throw new LogicException('Das Ticket ist bereits einem anderen Tisch zugeordnet.');
        }

        $this->selectedOrderId = $order->id;
    }

    public function assign(AssignTicketToHospitalityOrderAction $assignTicket): void
    {
        Gate::authorize(Permission::TicketsRedeem->value);
        $this->clearMessages();

        try {
            if ($this->selectedOrderId === null) {
                throw new LogicException('Bitte zuerst einen offenen Tisch auswählen.');
            }

            $ticket = $assignTicket->execute(
                $this->requireTicket(),
                HospitalityOrder::query()->findOrFail($this->selectedOrderId),
                $this->currentUser(),
            );

            $this->selectedOrderId = $ticket->assigned_order_id;
            $this->notice = 'Ticket wurde dem Tisch dauerhaft zugeordnet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function chooseEntitlement(int $entitlementId): void
    {
        $ticket = $this->requireTicket();
        $entitlement = TicketEntitlement::query()
            ->with('menu.groups')
            ->where('ticket_id', $ticket->id)
            ->findOrFail($entitlementId);

        $this->selectedEntitlementId = $entitlement->id;
        $this->redemptionQuantity = 1;
        $this->menuSelections = [];

        foreach ($entitlement->menu->groups as $group) {
            $this->menuSelections[$group->id] = [];
        }

        $this->note = '';
        $this->clearMessages();
    }

    public function redeem(RedeemTicketEntitlementAction $redeemTicket): void
    {
        Gate::authorize(Permission::TicketsRedeem->value);
        $this->clearMessages();

        try {
            if ($this->selectedOrderId === null || $this->selectedEntitlementId === null) {
                throw new LogicException('Ticket, Tisch und Menüberechtigung müssen ausgewählt sein.');
            }

            $selections = [];
            foreach ($this->menuSelections as $groupId => $productIds) {
                $selections[(int) $groupId] = array_map('intval', $productIds);
            }

            $redemption = $redeemTicket->execute(
                ticket: $this->requireTicket(),
                entitlement: TicketEntitlement::query()->findOrFail($this->selectedEntitlementId),
                order: HospitalityOrder::query()->findOrFail($this->selectedOrderId),
                actor: $this->currentUser(),
                selectedProductIdsByGroup: $selections,
                quantity: $this->redemptionQuantity,
                note: $this->note,
            );

            $this->selectedEntitlementId = null;
            $this->menuSelections = [];
            $this->redemptionQuantity = 1;
            $this->note = '';
            $this->notice = "{$redemption->quantity} Menüberechtigung(en) wurden eingelöst.";
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function switchUser(): void
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        $ticket = $this->ticketId !== null
            ? Ticket::query()
                ->with(['entitlements.menu.groups.products.product', 'assignedOrder.table.area', 'sale'])
                ->find($this->ticketId)
            : null;

        $selectedEntitlement = $this->selectedEntitlementId !== null
            ? TicketEntitlement::query()
                ->with(['menu.groups.products' => fn ($query) => $query
                    ->where('active', true)
                    ->with('product')])
                ->find($this->selectedEntitlementId)
            : null;

        return view('surfaces.waiter.ticket-redemption', [
            'ticket' => $ticket,
            'selectedEntitlement' => $selectedEntitlement,
            'openOrders' => HospitalityOrder::query()
                ->where('status', HospitalityOrderStatus::Open->value)
                ->with('table.area')
                ->orderBy('number')
                ->get(),
            'user' => $this->currentUser(),
        ]);
    }

    private function requireTicket(): Ticket
    {
        if ($this->ticketId === null) {
            throw new LogicException('Bitte zuerst ein Ticket erfassen.');
        }

        return Ticket::query()->findOrFail($this->ticketId);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function friendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof \InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Ticket-Aktion konnte nicht ausgeführt werden.';
    }
}
