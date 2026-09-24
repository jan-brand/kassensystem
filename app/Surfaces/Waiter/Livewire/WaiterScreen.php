<?php

namespace App\Surfaces\Waiter\Livewire;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\GetPosCatalogQuery;
use App\Modules\Hospitality\Actions\AddMenuToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\AddProductToHospitalityOrderAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Hospitality\Models\DiningArea;
use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Hospitality\Models\HospitalityOrder;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Hospitality\Models\OptionGroup;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.waiter')]
final class WaiterScreen extends Component
{
    public ?int $selectedAreaId = null;

    public ?int $selectedTableId = null;

    public ?int $selectedOrderId = null;

    public bool $openingTable = false;

    public string $orderNote = '';

    public ?int $selectedProductId = null;

    /** @var list<int|string> */
    public array $selectedOptionValueIds = [];

    public string $itemNote = '';

    public ?int $selectedMenuId = null;

    /** @var array<int|string, list<int|string>> */
    public array $menuSelections = [];

    public string $menuNote = '';

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::HospitalityAccess->value);
    }

    public function mount(): void
    {
        $this->selectedAreaId = DiningArea::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->value('id');
    }

    public function selectArea(int $areaId): void
    {
        DiningArea::query()->where('active', true)->findOrFail($areaId);

        $this->selectedAreaId = $areaId;
        $this->clearTableSelection();
    }

    public function selectTable(int $tableId): void
    {
        $this->clearMessages();

        $table = DiningTable::query()
            ->with(['area', 'openOrder'])
            ->where('active', true)
            ->findOrFail($tableId);

        if (! $table->area->active) {
            throw new LogicException('Der Bereich ist nicht aktiv.');
        }

        $this->selectedAreaId = $table->area_id;
        $this->selectedTableId = $table->id;
        $this->selectedOrderId = $table->openOrder?->id;
        $this->openingTable = $table->openOrder === null;
        $this->orderNote = '';
        $this->clearItemSelection();
    }

    public function openSelectedTable(OpenHospitalityOrderAction $openOrder): void
    {
        Gate::authorize(Permission::HospitalityOrdersManage->value);
        $this->clearMessages();

        if ($this->selectedTableId === null) {
            return;
        }

        try {
            $order = $openOrder->execute(
                DiningTable::query()->findOrFail($this->selectedTableId),
                $this->currentUser(),
                $this->orderNote,
            );

            $this->selectedOrderId = $order->id;
            $this->openingTable = false;
            $this->orderNote = '';
            $this->notice = "Vorgang {$order->number} wurde geöffnet.";
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function chooseProduct(int $productId): void
    {
        $this->requireOpenOrder();
        Product::query()->findOrFail($productId);

        $this->selectedProductId = $productId;
        $this->selectedOptionValueIds = [];
        $this->itemNote = '';
        $this->selectedMenuId = null;
        $this->menuSelections = [];
        $this->menuNote = '';
        $this->clearMessages();
    }

    public function addSelectedProduct(AddProductToHospitalityOrderAction $addProduct): void
    {
        Gate::authorize(Permission::HospitalityOrdersManage->value);
        $this->clearMessages();

        if ($this->selectedProductId === null) {
            return;
        }

        try {
            $addProduct->execute(
                order: $this->requireOpenOrder(),
                product: Product::query()->findOrFail($this->selectedProductId),
                optionValueIds: array_map('intval', $this->selectedOptionValueIds),
                note: $this->itemNote,
            );

            $this->clearItemSelection();
            $this->notice = 'Position wurde hinzugefügt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function chooseMenu(int $menuId): void
    {
        $this->requireOpenOrder();

        $menu = Menu::query()
            ->with('groups')
            ->where('active', true)
            ->findOrFail($menuId);

        $this->selectedMenuId = $menu->id;
        $this->menuSelections = [];

        foreach ($menu->groups as $group) {
            $this->menuSelections[$group->id] = [];
        }

        $this->menuNote = '';
        $this->selectedProductId = null;
        $this->selectedOptionValueIds = [];
        $this->itemNote = '';
        $this->clearMessages();
    }

    public function addSelectedMenu(AddMenuToHospitalityOrderAction $addMenu): void
    {
        Gate::authorize(Permission::HospitalityOrdersManage->value);
        $this->clearMessages();

        if ($this->selectedMenuId === null) {
            return;
        }

        $selections = [];

        foreach ($this->menuSelections as $groupId => $productIds) {
            $selections[(int) $groupId] = array_map('intval', $productIds);
        }

        try {
            $addMenu->execute(
                order: $this->requireOpenOrder(),
                menu: Menu::query()->findOrFail($this->selectedMenuId),
                selectedProductIdsByGroup: $selections,
                note: $this->menuNote,
            );

            $this->clearItemSelection();
            $this->notice = 'Menü wurde hinzugefügt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function closeItemDialog(): void
    {
        $this->clearItemSelection();
        $this->clearMessages();
    }

    public function switchUser(): void
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }

    public function render(GetPosCatalogQuery $catalogQuery): View
    {
        $areas = DiningArea::query()
            ->where('active', true)
            ->with([
                'tables' => fn ($query) => $query
                    ->where('active', true)
                    ->with(['openOrder.items'])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $selectedOrder = $this->selectedOrderId !== null
            ? HospitalityOrder::query()
                ->with(['table.area', 'items.options', 'items.components'])
                ->where('status', HospitalityOrderStatus::Open->value)
                ->find($this->selectedOrderId)
            : null;

        $selectedProduct = $this->selectedProductId !== null
            ? Product::query()->with('category')->find($this->selectedProductId)
            : null;

        $optionGroups = collect();

        if ($selectedProduct instanceof Product) {
            $optionGroups = OptionGroup::query()
                ->where('active', true)
                ->where(function ($query) use ($selectedProduct): void {
                    $query
                        ->whereHas('products', fn ($q) => $q->whereKey($selectedProduct->id))
                        ->orWhereHas('categories', fn ($q) => $q->whereKey($selectedProduct->category_id));
                })
                ->with([
                    'values' => fn ($query) => $query
                        ->where('active', true)
                        ->orderBy('sort_order')
                        ->orderBy('id'),
                ])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $selectedMenu = $this->selectedMenuId !== null
            ? Menu::query()
                ->with([
                    'groups.products' => fn ($query) => $query
                        ->where('active', true)
                        ->with('product'),
                ])
                ->where('active', true)
                ->find($this->selectedMenuId)
            : null;

        return view('surfaces.waiter.service', [
            'areas' => $areas,
            'selectedOrder' => $selectedOrder,
            'catalog' => $catalogQuery->execute(),
            'menus' => Menu::query()
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'selectedProduct' => $selectedProduct,
            'optionGroups' => $optionGroups,
            'selectedMenu' => $selectedMenu,
            'user' => $this->currentUser(),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
            'canUseRegister' => Gate::allows(Permission::PosAccess->value),
        ]);
    }

    private function requireOpenOrder(): HospitalityOrder
    {
        if ($this->selectedOrderId === null) {
            throw new LogicException('Bitte zuerst einen belegten Tisch auswählen oder einen Vorgang öffnen.');
        }

        return HospitalityOrder::query()
            ->where('status', HospitalityOrderStatus::Open->value)
            ->findOrFail($this->selectedOrderId);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function clearTableSelection(): void
    {
        $this->selectedTableId = null;
        $this->selectedOrderId = null;
        $this->openingTable = false;
        $this->orderNote = '';
        $this->clearItemSelection();
    }

    private function clearItemSelection(): void
    {
        $this->selectedProductId = null;
        $this->selectedOptionValueIds = [];
        $this->itemNote = '';
        $this->selectedMenuId = null;
        $this->menuSelections = [];
        $this->menuNote = '';
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function friendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
