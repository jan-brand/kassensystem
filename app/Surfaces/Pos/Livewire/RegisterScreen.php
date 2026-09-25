<?php

namespace App\Surfaces\Pos\Livewire;

use App\Modules\CashRegister\Actions\CancelCashSessionClosingAction;
use App\Modules\CashRegister\Actions\CloseCashSessionAction;
use App\Modules\CashRegister\Actions\EnsureDefaultRegisterAction;
use App\Modules\CashRegister\Actions\OpenCashSessionAction;
use App\Modules\CashRegister\Actions\RecordCashDepositAction;
use App\Modules\CashRegister\Actions\RecordCashWithdrawalAction;
use App\Modules\CashRegister\Enums\CashSessionStatus;
use App\Modules\CashRegister\Models\CashSession;
use App\Modules\CashRegister\Models\Register;
use App\Modules\CashRegister\Queries\GetActiveCashSessionQuery;
use App\Modules\CashRegister\Queries\GetCashSessionCashSummaryQuery;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Queries\GetPosCatalogQuery;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\AddProductToCartAction;
use App\Modules\Sales\Actions\ApplyManualDiscountToSaleItemAction;
use App\Modules\Sales\Actions\ChangeSaleItemQuantityAction;
use App\Modules\Sales\Actions\CompleteCashSaleAction;
use App\Modules\Sales\Actions\CompletePaypalSaleAction;
use App\Modules\Sales\Actions\DiscardOpenSaleAction;
use App\Modules\Sales\Actions\RemoveSaleItemDiscountAction;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Enums\PaymentMethod;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Sales\Queries\GetOpenSaleForRegisterQuery;
use App\Modules\Sales\Services\DigitalReceiptService;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use App\Modules\Sales\Services\PaypalMeLinkService;
use App\Modules\Sales\Services\QrCodeSvgService;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use App\Support\Money;
use App\Surfaces\Pos\Actions\StartRegisterClosingAction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
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

    public string $openingCash = '';

    public ?int $selectedCategoryId = null;

    public string $search = '';

    public string $receivedAmount = '';

    public bool $paymentOpen = false;

    public string $paymentMethod = 'cash';

    public bool $mobileCartOpen = false;

    public bool $cashMenuOpen = false;

    public ?string $cashMovementMode = null;

    public string $cashMovementAmount = '';

    public string $cashMovementReason = '';

    public string $closingCountedCash = '';

    public string $closingComment = '';

    public ?string $notice = null;

    public ?string $screenError = null;

    public ?string $lastSaleNumber = null;

    public ?int $lastSaleTotalCents = null;

    public ?int $lastChangeCents = null;

    public ?string $lastPaymentMethod = null;

    public ?string $lastReceiptUrl = null;

    public ?string $lastReceiptQrSvg = null;

    public ?int $discountItemId = null;

    public string $discountType = 'percentage';

    public string $discountValue = '10';

    public function boot(): void
    {
        Gate::authorize(Permission::PosAccess->value);
    }

    public function mount(EnsureDefaultRegisterAction $ensureRegister): void
    {
        $this->registerId = $ensureRegister->execute($this->currentUser())->id;
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
    }

    public function openMobileCart(): void
    {
        $this->mobileCartOpen = true;
    }

    public function closeMobileCart(): void
    {
        $this->mobileCartOpen = false;
    }

    public function openCashSession(OpenCashSessionAction $openSession): void
    {
        Gate::authorize(Permission::CashSessionsOpen->value);

        $this->clearMessages();

        try {
            $openingCashCents = trim($this->openingCash) === '' ? 0 : Money::parseCents($this->openingCash);

            $openSession->execute(
                register: $this->register(),
                user: $this->currentUser(),
                openingCashCents: $openingCashCents,
            );

            $this->openingCash = '';
            $this->notice = 'Kasse wurde geöffnet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function openCashMenu(): void
    {
        $this->clearMessages();

        try {
            $this->requireOpenCashSession();
            $this->mobileCartOpen = false;
            $this->cashMenuOpen = true;
            $this->cashMovementMode = null;
            $this->cashMovementAmount = '';
            $this->cashMovementReason = '';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function closeCashMenu(): void
    {
        $this->cashMenuOpen = false;
        $this->cashMovementMode = null;
        $this->cashMovementAmount = '';
        $this->cashMovementReason = '';
    }

    public function prepareCashMovement(string $mode): void
    {
        $this->clearMessages();

        if (! in_array($mode, ['deposit', 'withdrawal'], true)) {
            $this->screenError = 'Unbekannte Kassenbewegung.';

            return;
        }

        try {
            $this->requireOpenCashSession();
            $this->cashMenuOpen = true;
            $this->cashMovementMode = $mode;
            $this->cashMovementAmount = '';
            $this->cashMovementReason = '';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function recordCashMovement(
        RecordCashDepositAction $deposit,
        RecordCashWithdrawalAction $withdrawal,
    ): void {
        Gate::authorize(Permission::CashMovementsCreate->value);

        $this->clearMessages();

        try {
            $session = $this->requireOpenCashSession();
            $amountCents = Money::parseCents($this->cashMovementAmount);
            $reason = trim($this->cashMovementReason);

            if ($this->cashMovementMode === 'deposit') {
                $deposit->execute($session, $this->currentUser(), $amountCents, $reason);
                $this->notice = 'Einlage wurde erfasst.';
            } elseif ($this->cashMovementMode === 'withdrawal') {
                $withdrawal->execute($session, $this->currentUser(), $amountCents, $reason);
                $this->notice = 'Entnahme wurde erfasst.';
            } else {
                throw new LogicException('Bitte wähle zuerst Einlage oder Entnahme aus.');
            }

            $this->closeCashMenu();
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function startCashClosing(StartRegisterClosingAction $startClosing): void
    {
        Gate::authorize(Permission::CashSessionsClose->value);

        $this->clearMessages();

        try {
            $startClosing->execute($this->register(), $this->currentUser());

            $this->mobileCartOpen = false;
            $this->cashMenuOpen = false;
            $this->cashMovementMode = null;
            $this->closingCountedCash = '';
            $this->closingComment = '';
            $this->notice = 'Kassenabschluss wurde gestartet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function cancelCashClosing(CancelCashSessionClosingAction $cancelClosing): void
    {
        Gate::authorize(Permission::CashSessionsClose->value);

        $this->clearMessages();

        try {
            $session = $this->activeCashSession();

            if ($session === null) {
                throw new LogicException('Es gibt keine aktive Kassenschicht.');
            }

            $cancelClosing->execute($session, $this->currentUser());
            $this->closingCountedCash = '';
            $this->closingComment = '';
            $this->notice = 'Kassenabschluss wurde abgebrochen.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function closeCashSession(CloseCashSessionAction $closeSession): void
    {
        Gate::authorize(Permission::CashSessionsClose->value);

        $this->clearMessages();

        try {
            $session = $this->activeCashSession();

            if ($session === null) {
                throw new LogicException('Es gibt keine aktive Kassenschicht.');
            }

            $closed = $closeSession->execute(
                session: $session,
                user: $this->currentUser(),
                countedCashCents: Money::parseCents($this->closingCountedCash),
                comment: $this->closingComment,
            );

            $difference = (int) ($closed->closing_difference_cents ?? 0);
            $this->closingCountedCash = '';
            $this->closingComment = '';
            $this->notice = $difference === 0
                ? 'Kasse wurde ohne Differenz abgeschlossen.'
                : 'Kasse wurde mit dokumentierter Differenz abgeschlossen.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function addProduct(int $productId, AddProductToCartAction $addProduct): void
    {
        Gate::authorize(Permission::SalesCreate->value);

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

    public function openDiscount(int $itemId, DiscountPriceCalculator $calculator): void
    {
        Gate::authorize(Permission::SalesDiscountsApply->value);

        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $item = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->findOrFail($itemId);

            $this->discountItemId = $item->id;
            $this->discountType = $item->discount_type instanceof DiscountType ? $item->discount_type->value : DiscountType::Percentage->value;

            if ($item->discount_type === DiscountType::Percentage) {
                $this->discountValue = $calculator->formatPercentageBasisPoints((int) ($item->discount_value ?? 0));
            } elseif ($item->discount_type !== null) {
                $this->discountValue = Money::decimal((int) ($item->discount_value ?? 0));
            } else {
                $this->discountValue = '10';
            }
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function closeDiscount(): void
    {
        $this->discountItemId = null;
        $this->discountType = DiscountType::Percentage->value;
        $this->discountValue = '10';
    }

    public function applyManualDiscount(
        ApplyManualDiscountToSaleItemAction $action,
        DiscountPriceCalculator $calculator,
    ): void {
        Gate::authorize(Permission::SalesDiscountsApply->value);

        $this->clearMessages();

        try {
            if ($this->discountItemId === null) {
                throw new LogicException('Bitte zuerst eine Position auswählen.');
            }

            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $type = DiscountType::tryFrom($this->discountType);

            if (! $type instanceof DiscountType) {
                throw new InvalidArgumentException('Unbekannte Rabattart.');
            }

            $value = $type === DiscountType::Percentage
                ? $calculator->parsePercentageBasisPoints($this->discountValue)
                : Money::parseCents($this->discountValue);

            $item = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->findOrFail($this->discountItemId);

            $action->execute(
                item: $item,
                actor: $this->currentUser(),
                type: $type,
                value: $value,
            );

            $this->closeDiscount();
            $this->notice = 'Manueller Rabatt wurde angewendet.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function removeDiscount(RemoveSaleItemDiscountAction $action): void
    {
        Gate::authorize(Permission::SalesDiscountsApply->value);

        $this->clearMessages();

        try {
            if ($this->discountItemId === null) {
                throw new LogicException('Bitte zuerst eine Position auswählen.');
            }

            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $item = SaleItem::query()
                ->where('sale_id', $sale->id)
                ->findOrFail($this->discountItemId);

            $action->execute($item, $this->currentUser());

            $this->closeDiscount();
            $this->notice = 'Rabatt wurde entfernt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function showPayment(): void
    {
        Gate::authorize(Permission::SalesCreate->value);

        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null || $sale->items()->count() === 0) {
                throw new LogicException('Der Warenkorb ist leer.');
            }

            $this->mobileCartOpen = false;
            $this->paymentOpen = true;
            $this->paymentMethod = PaymentMethod::Cash->value;
            $this->receivedAmount = '';
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function setReceivedAmount(int $cents): void
    {
        $this->receivedAmount = Money::decimal($cents);
    }

    public function selectPaymentMethod(string $method): void
    {
        $this->clearMessages();

        try {
            $paymentMethod = PaymentMethod::tryFrom($method);

            if (! $paymentMethod instanceof PaymentMethod) {
                throw new LogicException('Unbekannte Zahlungsart.');
            }

            if ($paymentMethod === PaymentMethod::Paypal) {
                $sale = $this->saleForCurrentCashier();
                $handle = app(PaypalMeLinkService::class)->normalizeHandle(
                    app(GetSystemSettingsQuery::class)->execute()?->paypal_me_handle,
                );

                if ($sale === null || $sale->total_cents <= 0) {
                    throw new LogicException('Für diesen Verkauf ist keine PayPal.me-Zahlung erforderlich.');
                }

                if ($handle === null) {
                    throw new LogicException('PayPal.me ist nicht konfiguriert.');
                }
            }

            $this->paymentMethod = $paymentMethod->value;
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function completeSale(CompleteCashSaleAction $completeSale): void
    {
        Gate::authorize(Permission::SalesCreate->value);

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

            $this->rememberCompletedSale(
                $completed,
                $completed->total_cents > 0 ? PaymentMethod::Cash : null,
            );
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function completePaypalSale(CompletePaypalSaleAction $completePaypalSale): void
    {
        Gate::authorize(Permission::SalesCreate->value);

        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                throw new LogicException('Es gibt keinen offenen Verkauf.');
            }

            $completed = $completePaypalSale->execute($sale, $this->currentUser());

            $this->rememberCompletedSale($completed, PaymentMethod::Paypal);
        } catch (Throwable $exception) {
            $this->screenError = $this->friendlyMessage($exception);
        }
    }

    public function discardSale(DiscardOpenSaleAction $discard): void
    {
        Gate::authorize(Permission::SalesCreate->value);

        $this->clearMessages();

        try {
            $sale = $this->saleForCurrentCashier();

            if ($sale === null) {
                return;
            }

            $discard->execute($sale, $this->currentUser());
            $this->mobileCartOpen = false;
            $this->paymentOpen = false;
            $this->closeDiscount();
            $this->paymentMethod = PaymentMethod::Cash->value;
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
        $cashSummary = $session !== null
            ? app(GetCashSessionCashSummaryQuery::class)->execute($session)
            : null;
        $recentMovements = $session !== null
            ? $session->movements()->with('user')->latest('id')->limit(8)->get()
            : collect();
        $closingCountedCents = $session?->status === CashSessionStatus::Closing
            ? $this->parseOptionalMoney($this->closingCountedCash)
            : null;
        $closingDifferenceCents = $closingCountedCents !== null && $cashSummary !== null
            ? $closingCountedCents - $cashSummary['expected_cash_cents']
            : null;
        $cartItemCount = $sale !== null
            ? (int) $sale->items()->sum('quantity')
            : 0;
        $discountItem = null;

        if ($sale !== null && $this->discountItemId !== null) {
            $discountItem = $sale->items->first(
                fn (SaleItem $item): bool => $item->id === $this->discountItemId,
            );
        }

        $paypalPaymentUrl = null;
        $paypalQrSvg = null;

        if ($this->paymentOpen && $sale !== null && $sale->total_cents > 0) {
            try {
                $paypalLinks = app(PaypalMeLinkService::class);
                $paypalHandle = $paypalLinks->normalizeHandle($settings?->paypal_me_handle);

                if ($paypalHandle !== null) {
                    $paypalPaymentUrl = $paypalLinks->paymentUrl(
                        $paypalHandle,
                        $sale->total_cents,
                        (string) config('kassensystem.currency', 'EUR'),
                    );
                    $paypalQrSvg = app(QrCodeSvgService::class)->render($paypalPaymentUrl);
                }
            } catch (InvalidArgumentException $exception) {
                report($exception);
            }
        }

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
            'cashSummary' => $cashSummary,
            'recentMovements' => $recentMovements,
            'closingDifferenceCents' => $closingDifferenceCents,
            'cartItemCount' => $cartItemCount,
            'discountItem' => $discountItem,
            'foreignSale' => $sale !== null && $sale->cashier_id !== $user->id,
            'paypalPaymentUrl' => $paypalPaymentUrl,
            'paypalQrSvg' => $paypalQrSvg,
            'currency' => (string) config('kassensystem.currency', 'EUR'),
        ]);
    }

    private function changeItemQuantity(
        int $itemId,
        int $delta,
        ChangeSaleItemQuantityAction $changeQuantity,
    ): void {
        Gate::authorize(Permission::SalesCreate->value);

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

    private function activeCashSession(): ?CashSession
    {
        return app(GetActiveCashSessionQuery::class)->execute($this->register());
    }

    private function parseOptionalMoney(string $value): ?int
    {
        if (trim($value) === '') {
            return null;
        }

        try {
            return Money::parseCents($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function saleForCurrentCashier(): ?Sale
    {
        $sale = app(GetOpenSaleForRegisterQuery::class)->execute($this->register());

        if ($sale !== null && $sale->cashier_id !== $this->currentUser()->id) {
            throw new LogicException('Der offene Warenkorb gehört zu einem anderen Benutzer.');
        }

        return $sale;
    }

    private function rememberCompletedSale(Sale $sale, ?PaymentMethod $method): void
    {
        $this->lastSaleNumber = $sale->number;
        $this->lastSaleTotalCents = $sale->total_cents;
        $this->lastChangeCents = $method === PaymentMethod::Cash
            ? $sale->payment->change_cents
            : 0;
        $this->lastPaymentMethod = $method?->value;
        $this->lastReceiptUrl = null;
        $this->lastReceiptQrSvg = null;

        try {
            $receipts = app(DigitalReceiptService::class);
            $receipt = $receipts->ensureSaleReceipt($sale);

            if (! $receipt->isExpired()) {
                $receiptUrl = $receipts->publicUrl($receipt);
                $this->lastReceiptUrl = $receiptUrl;
                $this->lastReceiptQrSvg = app(QrCodeSvgService::class)->render($receiptUrl);
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        $this->mobileCartOpen = false;
        $this->paymentOpen = false;
        $this->closeDiscount();
        $this->paymentMethod = PaymentMethod::Cash->value;
        $this->receivedAmount = '';
        $this->notice = 'Verkauf abgeschlossen.';
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
        $this->lastSaleNumber = null;
        $this->lastSaleTotalCents = null;
        $this->lastChangeCents = null;
        $this->lastPaymentMethod = null;
        $this->lastReceiptUrl = null;
        $this->lastReceiptQrSvg = null;
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
