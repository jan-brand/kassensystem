<?php

namespace App\Modules\Sales\Queries;

use App\Modules\Catalog\Models\Product;
use App\Modules\Sales\Models\DiscountOffer;
use App\Modules\Sales\Services\DiscountPriceCalculator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class GetActiveDiscountOfferForProductQuery
{
    public function __construct(private readonly DiscountPriceCalculator $calculator) {}

    public function execute(Product $product, ?CarbonInterface $at = null): ?DiscountOffer
    {
        $moment = $at ?? CarbonImmutable::now((string) config('kassensystem.timezone', 'Europe/Berlin'));

        $offer = DiscountOffer::query()
            ->where('product_id', $product->id)
            ->where('active', true)
            ->first();

        if (! $offer instanceof DiscountOffer || ! $offer->isEffectiveAt($moment)) {
            return null;
        }

        try {
            $finalPrice = $this->calculator->finalUnitPrice(
                $product->price_cents,
                $offer->type,
                $offer->value,
            );
        } catch (InvalidArgumentException) {
            return null;
        }

        return $finalPrice < $product->price_cents ? $offer : null;
    }
}
