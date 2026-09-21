<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class AuthorizationService
{
    public function allows(?User $user, Permission|string $permission): bool
    {
        if (! $user instanceof User || ! $user->active) {
            return false;
        }

        $permission = $permission instanceof Permission
            ? $permission
            : Permission::tryFrom($permission);

        if (! $permission instanceof Permission) {
            return false;
        }

        return in_array($permission, $this->permissionsForRole($user->role), true);
    }

    /**
     * @return list<Permission>
     */
    public function permissionsForRole(UserRole $role): array
    {
        $cashier = [
            Permission::PosAccess,
            Permission::SalesCreate,
            Permission::CashSessionsOpen,
            Permission::CashSessionsClose,
            Permission::CashMovementsCreate,
        ];

        return match ($role) {
            UserRole::Cashier => $cashier,
            UserRole::Manager => [
                ...$cashier,
                Permission::AdministrationAccess,
                Permission::CatalogManage,
                Permission::UsersCashiersManage,
                Permission::SalesView,
                Permission::CashSessionsView,
                Permission::ReportsView,
            ],
            UserRole::Administrator => Permission::cases(),
        };
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(?User $user, Permission|string $permission): void
    {
        if (! $this->allows($user, $permission)) {
            throw new AuthorizationException('Diese Aktion ist nicht erlaubt.');
        }
    }
}
