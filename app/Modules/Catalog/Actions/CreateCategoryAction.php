<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Catalog\Models\Category;
use App\Modules\Identity\Models\User;
use InvalidArgumentException;

final class CreateCategoryAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(string $name, int $sortOrder = 0, ?User $actor = null): Category
    {
        $name = trim($name);

        if ($name === '' || $sortOrder < 0) {
            throw new InvalidArgumentException('Category name is required and sort order must not be negative.');
        }

        $category = Category::query()->create([
            'name' => $name,
            'active' => true,
            'sort_order' => $sortOrder,
        ]);

        $this->audit->execute(
            eventKey: 'category.created',
            actorUserId: $actor?->id,
            actorUsername: $actor?->username,
            actorDisplayName: $actor?->auditDisplayName(),
            subjectType: Category::class,
            subjectId: $category->id,
            after: $category->only(['name', 'active', 'sort_order']),
        );

        return $category;
    }
}
