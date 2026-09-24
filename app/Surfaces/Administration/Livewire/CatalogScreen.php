<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Catalog\Actions\CreateCategoryAction;
use App\Modules\Catalog\Actions\CreateProductAction;
use App\Modules\Catalog\Actions\SetProductActiveAction;
use App\Modules\Catalog\Actions\SetProductConsumableAction;
use App\Modules\Catalog\Actions\UpdateCategoryAction;
use App\Modules\Catalog\Actions\UpdateProductAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
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
final class CatalogScreen extends Component
{
    public string $categoryName = '';

    public int $categorySortOrder = 0;

    public ?int $editingCategoryId = null;

    public string $editCategoryName = '';

    public int $editCategorySortOrder = 0;

    public ?int $productCategoryId = null;

    public string $productName = '';

    public string $productShortName = '';

    public string $productPrice = '0,00';

    public int $productSortOrder = 0;

    public ?int $editingProductId = null;

    public ?int $editProductCategoryId = null;

    public string $editProductName = '';

    public string $editProductShortName = '';

    public string $editProductPrice = '0,00';

    public int $editProductSortOrder = 0;

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::CatalogManage->value);
    }

    public function createCategory(CreateCategoryAction $action): void
    {
        $this->clearMessages();

        try {
            $action->execute(
                name: $this->categoryName,
                sortOrder: $this->categorySortOrder,
                actor: $this->currentUser(),
            );
            $this->categoryName = '';
            $this->categorySortOrder = 0;
            $this->notice = 'Kategorie wurde angelegt.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function startEditCategory(int $id): void
    {
        $category = Category::query()->findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->editCategoryName = $category->name;
        $this->editCategorySortOrder = $category->sort_order;
    }

    public function saveCategory(UpdateCategoryAction $action): void
    {
        $this->clearMessages();

        if ($this->editingCategoryId === null) {
            return;
        }

        try {
            $action->execute(
                category: Category::query()->findOrFail($this->editingCategoryId),
                name: $this->editCategoryName,
                sortOrder: $this->editCategorySortOrder,
                actor: $this->currentUser(),
            );
            $this->editingCategoryId = null;
            $this->notice = 'Kategorie wurde gespeichert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function createProduct(CreateProductAction $action): void
    {
        $this->clearMessages();

        try {
            if ($this->productCategoryId === null) {
                throw new InvalidArgumentException('Bitte eine Kategorie auswählen.');
            }

            $action->execute(
                category: Category::query()->findOrFail($this->productCategoryId),
                name: $this->productName,
                shortName: $this->productShortName,
                priceCents: Money::parseCents($this->productPrice),
                sortOrder: $this->productSortOrder,
                actor: $this->currentUser(),
            );

            $this->productName = '';
            $this->productShortName = '';
            $this->productPrice = '0,00';
            $this->productSortOrder = 0;
            $this->notice = 'Produkt wurde angelegt.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function startEditProduct(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $this->editingProductId = $product->id;
        $this->editProductCategoryId = $product->category_id;
        $this->editProductName = $product->name;
        $this->editProductShortName = $product->short_name;
        $this->editProductPrice = Money::decimal($product->price_cents);
        $this->editProductSortOrder = $product->sort_order;
    }

    public function saveProduct(UpdateProductAction $action): void
    {
        $this->clearMessages();

        if ($this->editingProductId === null || $this->editProductCategoryId === null) {
            return;
        }

        try {
            $action->execute(
                product: Product::query()->findOrFail($this->editingProductId),
                category: Category::query()->findOrFail($this->editProductCategoryId),
                name: $this->editProductName,
                shortName: $this->editProductShortName,
                priceCents: Money::parseCents($this->editProductPrice),
                sortOrder: $this->editProductSortOrder,
                actor: $this->currentUser(),
            );
            $this->editingProductId = null;
            $this->notice = 'Produkt wurde gespeichert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function toggleProduct(int $id, SetProductActiveAction $action): void
    {
        $this->clearMessages();

        try {
            $product = Product::query()->findOrFail($id);
            $action->execute($product, ! $product->active, $this->currentUser());
            $this->notice = 'Produktstatus wurde geändert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function toggleProductConsumable(int $id, SetProductConsumableAction $action): void
    {
        $this->clearMessages();

        try {
            $product = Product::query()->findOrFail($id);
            $product = $action->execute($product, ! $product->is_consumable, $this->currentUser());
            $this->notice = $product->is_consumable
                ? 'Produkt ist als Verzehrartikel markiert.'
                : 'Produkt ist als Nicht-Verzehrartikel markiert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.catalog', [
            'categories' => Category::query()
                ->with('products')
                ->orderBy('sort_order')
                ->orderBy('id')
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

    private function message(Throwable $e): string
    {
        if ($e instanceof InvalidArgumentException || $e instanceof LogicException) {
            return $e->getMessage();
        }

        report($e);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
