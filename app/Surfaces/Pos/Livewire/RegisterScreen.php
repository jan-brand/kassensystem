<?php

namespace App\Surfaces\Pos\Livewire;

use App\Modules\CashRegister\Actions\EnsureDefaultRegisterAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\CashRegister\Queries\GetActiveCashSessionQuery;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\GetPosCatalogQuery;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\AddProductToCartAction;
use App\Modules\Sales\Actions\ChangeSaleItemQuantityAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\DiscardOpenSaleAction;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Queries\GetOpenSaleForRegisterQuery;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.pos')]
final class RegisterScreen extends Component
{
    public int $registerId;

    public string $openingCash = '0,00';

    public ?int $selectedCategoryId = null;

    public string $search = '';

    public string $receivedAmount = '';

    public bool $paymentOpen = false;

    public ?string $notice = null;

    public ?string $screenError = null;

    public ?string $lastSaleNumber = null;

    public ?int $lastSaleTotalCents = null;

    public ?int $lastChangeCents = null;

    public function mount(EnsureDefaultRegisterAction $ensureRegister): void
    {
        $this->registerId = $ensureRegister->execute($this->currentUser())->id;
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function openCashSession(OpenCashSessionAction $openSession): void
    {
        $this->clearMessages();

        try {
            $openSession->execute(
                register: $this->register(),
                user: $this->currentUser(),
                openingCashCents: Money::parseCents($this->openingCash),
            );

            $this->openingCash = '0,00';
            $this->notice = 'Kasse wurde geöffnet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function addProduct(int $productId, AddProductToCartAction $addProduct): void
    {
        $this->clearMessages();

        try {
            $session = $this->requireOpenCashSession();
            $product = Product::query()->findOrFail($productId);

            $addProduct->execute(
                register: $this->register(),
                cashSession: $session,
                cashier: $this->currentUser(),
                product: $product,
            );
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function increaseItem(int $itemId, ChangeSaleItemQuantityAction $changeQuantity): void
    {
        $this->changeItemQuantity($itemId, 1, $changeQuantity);
    }

    public function decreaseItem(int $itemId, ChangeSaleItemQuantityAction $changeQuantity): void
    {
        $this->changeItemQuantity($itemId, -1, $changeQuantity);
    }

    public function showPayment(): void
    {
        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null || $sale->items()->count() === 0) {
                throw new LogicException('Der Warenkorb ist leer.');
            }

            $this->paymentOpen = true;
            $this->receivedAmount = Money::decimal($sale->total_cents);
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function setReceivedAmount(int $cents): void
    {
        $this->receivedAmount = Money::decimal($cents);
    }

    public function completeSale(CompleteCashSaleAction $completeSale): void
    {
        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $receivedCents = $sale->total_cents === 0
                ? 0
                : Money::parseCents($this->receivedAmount);

            $completed = $completeSale->execute($sale, $receivedCents);

            $this->lastSaleNumber = $completed->number;
            $this->lastSaleTotalCents = $completed->total_cents;
            $this->lastChangeCents = $completed->payment?->change_cents ?? 0;
            $this->paymentOpen = false;
            $this->receivedAmount = '';
            $this->notice = 'Verkauf abgeschlossen.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function discardSale(DiscardOpenSaleAction $discard): void
    {
        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                return;
            }

            $discard->execute($sale, $this->currentUser());
            $this->paymentOpen = false;
            $this->receivedAmount = '';
            $this->notice = 'Warenkorb wurde verworfen.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function switchUser(): void
    {
        $this->clearMessages();

        $sale = app(GetOpenSaleForRegisterQuery::class)->execute($this->register());

        if ($sale !== null) {
            $this->screenError = 'Vor dem Benutzerwechsel muss der offene Warenkorb abgeschlossen oder verworfen werden.';

            return;
        }

        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();

        $this->redirectRoute('login', navigate: true);
    }

    public function render(): View
    {
        $register = $this->register();
        $session = app(GetActiveCashSessionQuery::class)->execute($register);
        $sale = app(GetOpenSaleForRegisterQuery::class)->execute($register);
        $catalog = app(GetPosCatalogQuery::class)->execute();
        $settings = app(GetSystemSettingsQuery::class)->execute();
        $user = $this->currentUser();

        $products = $catalog
            ->when(
                $this->selectedCategoryId !== null,
                fn (Collection $categories) => $categories->where('id', $this->selectedCategoryId),
            )
            ->flatMap(fn ($category) => $category->products)
            ->when(
                trim($this->search) !== '',
                function (Collection $products): Collection {
                    $needle = mb_strtolower(trim($this->search));

                    return $products->filter(
                        fn (Product $product): bool => str_contains(mb_strtolower($product->name), $needle)
                            || str_contains(mb_strtolower($product->short_name), $needle),
                    );
                },
            )
            ->values();

        return view('surfaces.pos.register', [
            'register' => $register,
            'session' => $session,
            'sale' => $sale,
            'catalog' => $catalog,
            'products' => $products,
            'user' => $user,
            'settings' => $settings,
            'foreignSale' => $sale !== null && $sale->cashier_id !== $user->id,
            'currency' => (string) config('kassensystem.currency', 'EUR'),
        ]);
    }

    private function changeItemQuantity(
        int $itemId,
        int $delta,
        ChangeSaleItemQuantityAction $changeQuantity,
    ): void {
        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $item = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->findOrFail($itemId);

            $changeQuantity->execute($item, max(0, $item->quantity + $delta));
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    private function currentUser(): User
    {
        $id = Auth::id();

        if ($id === null) {
            throw new LogicException('Authentication required.');
        }

        return User::query()->findOrFail($id);
    }

    private function register(): Register
    {
        return Register::query()->findOrFail($this->registerId);
    }

    private function requireOpenCashSession(): CashSession
    {
        $session = app(GetActiveCashSessionQuery::class)->execute($this->register());

        if ($session === null || $session->status !== CashSessionStatus::Open) {
            throw new LogicException('Die Kasse ist nicht geöffnet.');
        }

        return $session;
    }

    private function saleForCurrentCashier(): ?Sale
    {
        $sale = app(GetOpenSaleForRegisterQuery::class)->execute($this->register());

        if ($sale !== null && $sale->cashier_id !== $this->currentUser()->id) {
            throw new LogicException('Der offene Warenkorb gehört zu einem anderen Benutzer.');
        }

        return $sale;
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
        $this->lastSaleNumber = null;
        $this->lastSaleTotalCents = null;
        $this->lastChangeCents = null;
    }

    private function friendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
