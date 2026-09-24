<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Hospitality\Models\OptionGroup;
use InvalidArgumentException;

final class CreateOptionGroupAction
{
    public function execute(
        string $name,
        int $minChoices = 0,
        int $maxChoices = 1,
        int $sortOrder = 0,
    ): OptionGroup {
        $name = trim($name);

        if (
            $name === ''
            || mb_strlen($name) > 160
            || $minChoices < 0
            || $maxChoices < 1
            || $minChoices > $maxChoices
            || $sortOrder < 0
        ) {
            throw new InvalidArgumentException('Ungültige Optionsgruppen-Regeln.');
        }

        return OptionGroup::query()->create([
            'name' => $name,
            'min_choices' => $minChoices,
            'max_choices' => $maxChoices,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);
    }
}
