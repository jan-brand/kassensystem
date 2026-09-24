<?php

namespace App\Modules\Preparation\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Identity\Models\User;
use App\Modules\Preparation\Enums\PreparationTaskStatus;
use App\Modules\Preparation\Models\PreparationTask;
use Illuminate\Support\Facades\DB;
use LogicException;

final class AdvancePreparationTaskAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(
        PreparationTask $task,
        PreparationTaskStatus $target,
        User $actor,
    ): PreparationTask {
        return DB::transaction(function () use ($task, $target, $actor): PreparationTask {
            $locked = PreparationTask::query()
                ->with('order')
                ->lockForUpdate()
                ->findOrFail($task->id);

            if ($locked->status === $target) {
                return $locked;
            }

            if ($locked->order->status !== HospitalityOrderStatus::Open) {
                throw new LogicException('Geschlossene Tischvorgänge können nicht weiter zubereitet werden.');
            }

            $allowed = match ($locked->status) {
                PreparationTaskStatus::New => PreparationTaskStatus::InPreparation,
                PreparationTaskStatus::InPreparation => PreparationTaskStatus::Ready,
                PreparationTaskStatus::Ready => null,
            };

            if ($allowed !== $target) {
                throw new LogicException('Dieser Stationsstatus kann nicht übersprungen oder zurückgesetzt werden.');
            }

            $before = ['status' => $locked->status->value];

            if ($target === PreparationTaskStatus::InPreparation) {
                $locked->update([
                    'status' => $target,
                    'started_by_user_id' => $actor->id,
                    'started_at' => now(),
                ]);
            } else {
                $locked->update([
                    'status' => $target,
                    'ready_by_user_id' => $actor->id,
                    'ready_at' => now(),
                ]);
            }

            $this->audit->execute(
                eventKey: $target === PreparationTaskStatus::InPreparation
                    ? 'preparation.task.started'
                    : 'preparation.task.ready',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: PreparationTask::class,
                subjectId: $locked->id,
                before: $before,
                after: ['status' => $target->value, 'station_id' => $locked->station_id],
            );

            return $locked->refresh();
        });
    }
}
