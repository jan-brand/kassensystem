<?php

namespace App\Modules\Hospitality\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Models\Menu;
use App\Modules\Identity\Models\User;

final class ActivateMenuAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Menu $menu, ?User $actor = null): Menu
    {
        if ($menu->active) {
            return $menu;
        }

        $before = ['active' => false];

        $menu->update(['active' => true]);
        $menu->refresh();

        $this->audit->execute(
            eventKey: 'hospitality.menu.status_changed',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Menu::class,
            subjectId: $menu->id,
            before: $before,
            after: ['active' => $menu->active],
        );

        return $menu;
    }
}
