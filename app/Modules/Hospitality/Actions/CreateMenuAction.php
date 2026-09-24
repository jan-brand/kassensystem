<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class CreateMenuAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        string $name,
        int $priceCents,
        int $sortOrder = 0,
        ?User $actor = null,
    ): Menu {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 160 || $priceCents < 0 || $sortOrder < 0) {
            throw new InvalidArgumentException('Ungültige Menüdaten.');
        }

        $menu = Menu::query()->create([
            'name' => $name,
            'price_cents' => $priceCents,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'hospitality.menu.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Menu::class,
            subjectId: $menu->id,
            after: $menu->only(['name', 'price_cents', 'active', 'sort_order']),
        );

        return $menu;
    }
}
