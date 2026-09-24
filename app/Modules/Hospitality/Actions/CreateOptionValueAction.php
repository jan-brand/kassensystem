<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Hospitality\Models\OptionGroup;
use App\Modules\Hospitality\Models\OptionValue;
use InvalidArgumentException;

final class CreateOptionValueAction
{
    public function execute(
        OptionGroup $group,
        string $name,
        int $priceDeltaCents = 0,
        int $sortOrder = 0,
    ): OptionValue {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 160 || $priceDeltaCents < 0 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Optionsdaten.');
        }

        return OptionValue::query()->create([
            'option_group_id' => $group->id,
            'name' => $name,
            'price_delta_cents' => $priceDeltaCents,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
