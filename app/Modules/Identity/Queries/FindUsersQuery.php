<?php

namespace App\Modules\Identity\Queries;

use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class FindUsersQuery
{
    public function execute(
        ?string $search = null,
        ?UserRole $role = null,
        ?bool $active = null,
        int $perPage = 50,
    ): LengthAwarePaginator {
        return User::query()
            ->when($search, function ($query, string $value): void {
                $query->where(function ($query) use ($value): void {
                    $query
                        ->where('username', 'like', "%{$value}%")
                        ->orWhere('first_name', 'like', "%{$value}%")
                        ->orWhere('last_name', 'like', "%{$value}%")
                        ->orWhere('display_name', 'like', "%{$value}%");
                });
            })
            ->when($role, fn ($query, UserRole $value) => $query->where('role', $value->value))
            ->when($active !== null, fn ($query) => $query->where('active', $active))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage);
    }
}
