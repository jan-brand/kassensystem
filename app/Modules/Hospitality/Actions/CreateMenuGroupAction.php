<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Hospitality\Models\Menu;
use App\Modules\Hospitality\Models\MenuGroup;
use InvalidArgumentException;

final class CreateMenuGroupAction
{
    public function execute(
        Menu $menu,
        string $name,
        int $minChoices,
        int $maxChoices,
        int $sortOrder = 0,
    ): MenuGroup {
        $name = trim($name);

        if (
            $name === ''
            || mb_strlen($name) > 160
            || $minChoices < 0
            || $maxChoices < 1
            || $minChoices > $maxChoices
            || $sortOrder < 0
        ) {
            throw new InvalidArgumentException('Ungültige Menügruppen-Regeln.');
        }

        return MenuGroup::query()->create([
            'menu_id' => $menu->id,
            'name' => $name,
            'min_choices' => $minChoices,
            'max_choices' => $maxChoices,
            'sort_order' => $sortOrder,
        ]);
    }
}
