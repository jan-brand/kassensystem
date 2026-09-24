<?php

namespace App\Providers;

use App\Modules\Hospitality\Models\HospitalityOrderItem;
use App\Modules\Hospitality\Models\HospitalityOrderItemComponent;
use App\Modules\Hospitality\Models\HospitalityOrderItemOption;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Preparation\Services\PreparationDispatchService;
use App\Modules\Preparation\Services\PreparationMutationGuard;
use App\Modules\Preparation\Services\PreparationSynchronizationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(AuthorizationService $authorization): void
    {
        foreach (Permission::cases() as $permission) {
            Gate::define(
                $permission->value,
                static fn (User $user): bool => $authorization->allows($user, $permission),
            );
        }

        HospitalityOrderItem::created(
            static fn (HospitalityOrderItem $item) => app(PreparationDispatchService::class)->dispatchItem($item),
        );
        HospitalityOrderItemComponent::created(
            static fn (HospitalityOrderItemComponent $component) => app(PreparationDispatchService::class)->dispatchComponent($component),
        );

        HospitalityOrderItem::updating(
            static fn (HospitalityOrderItem $item) => app(PreparationMutationGuard::class)->assertItemMutable($item),
        );
        HospitalityOrderItem::updated(
            static fn (HospitalityOrderItem $item) => app(PreparationSynchronizationService::class)->syncItem($item),
        );
        HospitalityOrderItem::deleting(
            static fn (HospitalityOrderItem $item) => app(PreparationMutationGuard::class)->assertItemMutable($item),
        );
        HospitalityOrderItemComponent::updating(
            static fn (HospitalityOrderItemComponent $component) => app(PreparationMutationGuard::class)->assertComponentMutable($component),
        );
        HospitalityOrderItemComponent::updated(
            static fn (HospitalityOrderItemComponent $component) => app(PreparationSynchronizationService::class)->syncComponent($component),
        );
        HospitalityOrderItemComponent::deleting(
            static fn (HospitalityOrderItemComponent $component) => app(PreparationMutationGuard::class)->assertComponentMutable($component),
        );
        HospitalityOrderItemOption::updating(
            static fn (HospitalityOrderItemOption $option) => app(PreparationMutationGuard::class)->assertOptionMutable($option),
        );
        HospitalityOrderItemOption::deleting(
            static fn (HospitalityOrderItemOption $option) => app(PreparationMutationGuard::class)->assertOptionMutable($option),
        );
    }
}
