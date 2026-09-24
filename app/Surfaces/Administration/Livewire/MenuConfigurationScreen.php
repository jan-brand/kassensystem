<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Catalog\Models\Product;
use App\Modules\Hospitality\Actions\ActivateMenuAction;
use App\Modules\Hospitality\Actions\AddProductToMenuGroupAction;
use App\Modules\Hospitality\Actions\CreateMenuAction;
use App\Modules\Hospitality\Actions\CreateMenuGroupAction;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Hospitality\Models\MenuGroup;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class MenuConfigurationScreen extends Component
{
    public string $menuName = '';

    public string $menuPrice = '0,00';

    public int $menuSortOrder = 0;

    public ?int $groupMenuId = null;

    public string $groupName = '';

    public int $groupMinChoices = 1;

    public int $groupMaxChoices = 1;

    public int $groupSortOrder = 0;

    public ?int $productGroupId = null;

    public ?int $productId = null;

    public string $productPriceDelta = '0,00';

    public int $productSortOrder = 0;

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::HospitalityConfigurationManage->value);
    }

    public function createMenu(CreateMenuAction $action): void
    {
        $this->clearMessages();

        try {
            $menu = $action->execute(
                name: $this->menuName,
                priceCents: Money::parseCents($this->menuPrice),
                sortOrder: $this->menuSortOrder,
                actor: $this->currentUser(),
            );

            $this->menuName = '';
            $this->menuPrice = '0,00';
            $this->menuSortOrder = 0;
            $this->groupMenuId = $menu->id;
            $this->notice = 'Menü wurde angelegt. Lege jetzt mindestens eine Auswahlgruppe an.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function createGroup(CreateMenuGroupAction $action): void
    {
        $this->clearMessages();

        try {
            if ($this->groupMenuId === null) {
                throw new InvalidArgumentException('Bitte zuerst ein Menü auswählen.');
            }

            $group = $action->execute(
                menu: Menu::query()->where('active', true)->findOrFail($this->groupMenuId),
                name: $this->groupName,
                minChoices: $this->groupMinChoices,
                maxChoices: $this->groupMaxChoices,
                sortOrder: $this->groupSortOrder,
            );

            $this->groupName = '';
            $this->groupMinChoices = 1;
            $this->groupMaxChoices = 1;
            $this->groupSortOrder = 0;
            $this->productGroupId = $group->id;
            $this->notice = 'Auswahlgruppe wurde angelegt. Weise ihr jetzt Produkte zu.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function addProduct(AddProductToMenuGroupAction $action): void
    {
        $this->clearMessages();

        try {
            if ($this->productGroupId === null || $this->productId === null) {
                throw new InvalidArgumentException('Bitte Auswahlgruppe und Produkt auswählen.');
            }

            $group = MenuGroup::query()
                ->whereHas('menu', fn ($query) => $query->where('active', true))
                ->findOrFail($this->productGroupId);

            $product = Product::query()
                ->where('active', true)
                ->whereHas('category', fn ($query) => $query->where('active', true))
                ->findOrFail($this->productId);

            $action->execute(
                group: $group,
                product: $product,
                priceDeltaCents: Money::parseCents($this->productPriceDelta),
                sortOrder: $this->productSortOrder,
            );

            $this->productId = null;
            $this->productPriceDelta = '0,00';
            $this->productSortOrder = 0;
            $this->notice = 'Produkt wurde der Auswahlgruppe hinzugefügt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function activateMenu(int $menuId, ActivateMenuAction $action): void
    {
        $this->clearMessages();

        try {
            $menu = Menu::query()->findOrFail($menuId);
            $action->execute($menu, $this->currentUser());
            $this->notice = 'Menü wurde aktiviert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function render(): View
    {
        $menus = Menu::query()
            ->with([
                'groups.products' => fn ($query) => $query
                    ->with('product.category')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('surfaces.administration.menu-configuration', [
            'menus' => $menus,
            'groups' => MenuGroup::query()
                ->with('menu')
                ->whereHas('menu', fn ($query) => $query->where('active', true))
                ->orderBy('menu_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'products' => Product::query()
                ->with('category')
                ->where('active', true)
                ->whereHas('category', fn ($query) => $query->where('active', true))
                ->orderBy('name')
                ->get(),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
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
        if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Menü-Konfiguration konnte nicht gespeichert werden.';
    }
}
