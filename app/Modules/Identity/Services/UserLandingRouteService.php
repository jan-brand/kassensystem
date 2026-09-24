<?php

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;

final class UserLandingRouteService
{
    public function __construct(private readonly AuthorizationService $authorization) {}

    public function for(User $user): string
    {
        if ($this->authorization->allows($user, Permission::PosAccess)) {
            return 'pos.register';
        }

        if ($this->authorization->allows($user, Permission::HospitalityAccess)) {
            return 'waiter.service';
        }

        if ($this->authorization->allows($user, Permission::AdministrationAccess)) {
            return 'administration.dashboard';
        }

        return 'login';
    }
}
