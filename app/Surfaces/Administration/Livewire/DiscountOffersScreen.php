<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Sales\Actions\SaveDiscountOfferAction;
use App\Modules\Sales\Actions\SetDiscountOfferActiveAction;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Models\DiscountOffer;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class DiscountOffersScreen extends Component
{
    public ?int $editingOfferId = null;

    public ?int $productId = null;

    public string $name = '';

    public string $discountType = 'percentage';

    public string $discountValue = '10';

    public bool $active = true;

    public string $startsAt = '';

    public string $endsAt = '';

    /** @var list<string> */
    public array $weekdays = [];

    public string $dailyStartTime = '';

    public string $dailyEndTime = '';

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::CatalogManage->value);
    }

    public function edit(int $offerId, DiscountPriceCalculator $calculator): void
    {
        $offer = DiscountOffer::query()->findOrFail($offerId);

        $this->editingOfferId = $offer->id;
        $this->productId = $offer->product_id;
        $this->name = $offer->name;
        $this->discountType = $offer->type->value;
        $this->discountValue = $offer->type === DiscountType::Percentage
            ? $calculator->formatPercentageBasisPoints($offer->value)
            : Money::decimal($offer->value);
        $this->active = $offer->active;
        $this->startsAt = $offer->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $offer->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->weekdays = array_map('strval', $offer->weekdays ?? []);
        $this->dailyStartTime = $offer->daily_start_time ?? '';
        $this->dailyEndTime = $offer->daily_end_time ?? '';
        $this->clearMessages();
    }

    public function save(
        SaveDiscountOfferAction $action,
        DiscountPriceCalculator $calculator,
    ): void {
        $this->clearMessages();

        try {
            if ($this->productId === null) {
                throw new InvalidArgumentException('Bitte ein Produkt auswählen.');
            }

            $type = DiscountType::tryFrom($this->discountType);

            if (! $type instanceof DiscountType) {
                throw new InvalidArgumentException('Unbekannte Rabattart.');
            }

            $value = $type === DiscountType::Percentage
                ? $calculator->parsePercentageBasisPoints($this->discountValue)
                : Money::parseCents($this->discountValue);

            $action->execute(
                actor: $this->currentUser(),
                product: Product::query()->findOrFail($this->productId),
                name: $this->name,
                type: $type,
                value: $value,
                active: $this->active,
                startsAt: $this->parseDateTime($this->startsAt),
                endsAt: $this->parseDateTime($this->endsAt),
                weekdays: array_map('intval', $this->weekdays),
                dailyStartTime: $this->dailyStartTime,
                dailyEndTime: $this->dailyEndTime,
                offer: $this->editingOfferId !== null
                    ? DiscountOffer::query()->findOrFail($this->editingOfferId)
                    : null,
            );

            $this->resetForm();
            $this->notice = 'Tagesangebot wurde gespeichert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function toggle(int $offerId, SetDiscountOfferActiveAction $action): void
    {
        $this->clearMessages();

        try {
            $offer = DiscountOffer::query()->findOrFail($offerId);
            $action->execute($this->currentUser(), $offer, ! $offer->active);
            $this->notice = 'Angebotsstatus wurde geändert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
        $this->clearMessages();
    }

    public function render(): View
    {
        return view('surfaces.administration.discount-offers', [
            'offers' => DiscountOffer::query()
                ->with('product.category')
                ->orderBy('id')
                ->get(),
            'products' => Product::query()
                ->with('category')
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
            'types' => DiscountType::cases(),
            'currency' => (string) config('kassensystem.currency', 'EUR'),
        ]);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function parseDateTime(string $value): ?CarbonInterface
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timezone = (string) config('kassensystem.timezone', 'Europe/Berlin');
        $date = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, $timezone);

        if (! $date instanceof CarbonImmutable || $date->format('Y-m-d\TH:i') !== $value) {
            throw new InvalidArgumentException('Ungültiger Zeitpunkt.');
        }

        return $date;
    }

    private function resetForm(): void
    {
        $this->editingOfferId = null;
        $this->productId = null;
        $this->name = '';
        $this->discountType = DiscountType::Percentage->value;
        $this->discountValue = '10';
        $this->active = true;
        $this->startsAt = '';
        $this->endsAt = '';
        $this->weekdays = [];
        $this->dailyStartTime = '';
        $this->dailyEndTime = '';
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function message(Throwable $exception): string
    {
        if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
