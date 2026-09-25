<?php

namespace App\Modules\Sales\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Sales\Models\DiscountOffer;
use Illuminate\Support\Facades\DB;

final class SetDiscountOfferActiveAction
{
    public function __construct(
        private readonly AuthorizationService $authorization,
        private readonly WriteAuditEventAction $audit,
    ) {}

    public function execute(User $actor, DiscountOffer $offer, bool $active): DiscountOffer
    {
        $this->authorization->authorize($actor, Permission::CatalogManage);

        return DB::transaction(function () use ($actor, $offer, $active): DiscountOffer {
            $locked = DiscountOffer::query()->lockForUpdate()->findOrFail($offer->id);
            $before = ['active' => $locked->active];

            $locked->update(['active' => $active]);

            $this->audit->execute(
                eventKey: 'discount.offer.status_changed',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: DiscountOffer::class,
                subjectId: $locked->id,
                before: $before,
                after: ['active' => $locked->active],
            );

            return $locked->refresh();
        });
    }
}
