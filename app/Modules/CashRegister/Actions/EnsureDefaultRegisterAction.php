<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EnsureDefaultRegisterAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(?User $actor = null): Register
    {
        return DB::transaction(function () use ($actor): Register {
            $active = Register::query()
                ->where('active', true)
                ->orderBy('id')
                ->first();

            if ($active !== null) {
                return $active;
            }

            if (Register::query()->exists()) {
                throw new LogicException('No active register is configured.');
            }

            $register = Register::query()->create([
                'code' => 'register-1',
                'name' => (string) config('kassensystem.register_name', 'Kasse 1'),
                'active' => true,
            ]);

            $this->audit->execute(
                eventKey: 'register.created',
                actorUserId: $actor?->id,
                actorUsername: $actor?->username,
                actorDisplayName: $actor?->auditDisplayName(),
                subjectType: Register::class,
                subjectId: $register->id,
                after: [
                    'code' => $register->code,
                    'name' => $register->name,
                    'active' => $register->active,
                ],
            );

            return $register;
        });
    }
}
