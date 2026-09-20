<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class UpdateCategoryAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Category $category, string $name, int $sortOrder, ?User $actor = null): Category
    {
        $name = trim($name);

        if ($name === '' || $sortOrder < 0) {
            throw new InvalidArgumentException('Category name is required and sort order must not be negative.');
        }

        $before = $category->only(['name', 'active', 'sort_order']);

        $category->update([
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);

        $fresh = $category->refresh();

        $this->audit->execute(
            eventKey: 'category.updated',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Category::class,
            subjectId: $fresh->id,
            before: $before,
            after: $fresh->only(['name', 'active', 'sort_order']),
        );

        return $fresh;
    }
}
