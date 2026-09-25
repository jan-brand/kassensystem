<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Product;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Sales\Enums\DiscountType;
use App\Modules\Sales\Models\DiscountOffer;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SaveDiscountOfferAction
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
        private readonly DiscountPriceCalculator $calculator,
    ) {}

    /**
     * @param  list<int>  $weekdays
     */
    public function execute(
        User $actor,
        Product $product,
        string $name,
        DiscountType $type,
        int $value,
        bool $active,
        ?CarbonInterface $startsAt = null,
        ?CarbonInterface $endsAt = null,
        array $weekdays = [],
        ?string $dailyStartTime = null,
        ?string $dailyEndTime = null,
        ?DiscountOffer $offer = null,
    ): DiscountOffer {
        $this->authorization->authorize($actor, Permission::CatalogManage);

        $name = trim($name);
        $dailyStartTime = $this->normalizeTime($dailyStartTime);
        $dailyEndTime = $this->normalizeTime($dailyEndTime);
        $weekdays = array_values(array_unique(array_map('intval', $weekdays)));
        sort($weekdays);

        if ($name === '' || mb_strlen($name) > 160) {
            throw new InvalidArgumentException('Angebotsname muss 1 bis 160 Zeichen lang sein.');
        }

        if ($startsAt !== null && $endsAt !== null && $endsAt->lt($startsAt)) {
            throw new InvalidArgumentException('Das Angebotsende darf nicht vor dem Start liegen.');
        }

        foreach ($weekdays as $weekday) {
            if ($weekday < 1 || $weekday > 7) {
                throw new InvalidArgumentException('Ungültiger Wochentag.');
            }
        }

        if (($dailyStartTime === null) !== ($dailyEndTime === null)) {
            throw new InvalidArgumentException('Start- und Endzeit müssen gemeinsam gesetzt werden.');
        }

        if ($dailyStartTime !== null && $dailyEndTime !== null && $dailyEndTime < $dailyStartTime) {
            throw new InvalidArgumentException('Das tägliche Zeitfenster darf nicht über Mitternacht laufen.');
        }

        $this->calculator->finalUnitPrice($product->price_cents, $type, $value);

        return DB::transaction(function () use (
            $actor,
            $product,
            $name,
            $type,
            $value,
            $active,
            $startsAt,
            $endsAt,
            $weekdays,
            $dailyStartTime,
            $dailyEndTime,
            $offer,
        ): DiscountOffer {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $lockedOffer = $offer instanceof DiscountOffer
                ? DiscountOffer::query()->lockForUpdate()->findOrFail($offer->id)
                : new DiscountOffer;

            $duplicateQuery = DiscountOffer::query()
                ->where('product_id', $lockedProduct->id);

            if ($lockedOffer->exists) {
                $duplicateQuery->where('id', '!=', $lockedOffer->id);
            }

            if ($duplicateQuery->exists()) {
                throw new InvalidArgumentException('Für dieses Produkt existiert bereits ein Tagesangebot.');
            }

            $before = $lockedOffer->exists ? $this->snapshot($lockedOffer) : [];

            $lockedOffer->fill([
                'product_id' => $lockedProduct->id,
                'name' => $name,
                'type' => $type,
                'value' => $value,
                'active' => $active,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'weekdays' => $weekdays === [] ? null : $weekdays,
                'daily_start_time' => $dailyStartTime,
                'daily_end_time' => $dailyEndTime,
            ]);
            $lockedOffer->save();

            $this->audit->execute(
                eventKey: 'discount.offer.saved',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiscountOffer::class,
                subjectId: $lockedOffer->id,
                before: $before,
                after: $this->snapshot($lockedOffer),
            );

            return $lockedOffer->refresh()->load('product');
        });
    }

    private function normalizeTime(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value)) {
            throw new InvalidArgumentException('Zeitfenster muss im Format HH:MM angegeben werden.');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function snapshot(DiscountOffer $offer): array
    {
        return [
            'product_id' => $offer->product_id,
            'name' => $offer->name,
            'type' => $offer->type->value,
            'value' => $offer->value,
            'active' => $offer->active,
            'starts_at' => $offer->starts_at?->toIso8601String(),
            'ends_at' => $offer->ends_at?->toIso8601String(),
            'weekdays' => $offer->weekdays,
            'daily_start_time' => $offer->daily_start_time,
            'daily_end_time' => $offer->daily_end_time,
        ];
    }
}
