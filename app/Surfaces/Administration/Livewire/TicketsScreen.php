<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Hospitality\Models\Menu;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Enums\SaleStatus;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tickets\Actions\IssueTicketAction;
use App\Modules\Tickets\Enums\TicketFundingType;
use App\Modules\Tickets\Models\Ticket;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.administration')]
final class TicketsScreen extends Component
{
    public string $fundingType = 'free';

    public string $validOn = '';

    public string $saleNumber = '';

    /** @var array<int|string, int|string> */
    public array $menuQuantities = [];

    public ?string $issuedToken = null;

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::TicketsManage->value);
    }

    public function mount(): void
    {
        $this->validOn = today()->toDateString();
    }

    public function issue(IssueTicketAction $issueTicket): void
    {
        $this->clearMessages();
        $this->issuedToken = null;

        try {
            $fundingType = TicketFundingType::from($this->fundingType);
            $sale = null;

            if ($fundingType === TicketFundingType::Paid) {
                $sale = Sale::query()
                    ->where('status', SaleStatus::Completed->value)
                    ->where('number', trim($this->saleNumber))
                    ->first();

                if (! $sale instanceof Sale) {
                    $this->screenError = 'Der angegebene abgeschlossene Verkauf wurde nicht gefunden.';

                    return;
                }
            }

            $quantities = [];
            foreach ($this->menuQuantities as $menuId => $quantity) {
                $quantity = (int) $quantity;
                if ($quantity > 0) {
                    $quantities[(int) $menuId] = $quantity;
                }
            }

            $result = $issueTicket->execute(
                fundingType: $fundingType,
                validOn: CarbonImmutable::parse($this->validOn),
                menuQuantities: $quantities,
                actor: $this->currentUser(),
                sale: $sale,
            );

            $this->issuedToken = $result['token'];
            $this->notice = 'Ticket wurde ausgegeben. Der QR-Inhalt wird aus Sicherheitsgründen nur jetzt angezeigt.';
            $this->saleNumber = '';
            $this->menuQuantities = [];
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.tickets', [
            'menus' => Menu::query()
                ->where('active', true)
                ->withCount('groups')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'tickets' => Ticket::query()
                ->with(['entitlements', 'sale', 'assignedOrder'])
                ->latest('id')
                ->limit(50)
                ->get(),
        ]);
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
        if ($exception instanceof \InvalidArgumentException || $exception instanceof \LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Das Ticket konnte nicht ausgegeben werden.';
    }
}
