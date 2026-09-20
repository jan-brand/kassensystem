<?php

namespace App\Modules\CashRegister\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RenameRegisterAction
{
    public function __construct(private readonly WriteAuditEventAction $audit) {}

    public function execute(Register $register, string $name, User $actor): Register
    {
        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Register name must not be empty.');
        }

        if (mb_strlen($name) > 120) {
            throw new InvalidArgumentException('Register name must not be longer than 120 characters.');
        }

        return DB::transaction(function () use ($register, $name, $actor): Register {
            $locked = Register::query()->lockForUpdate()->findOrFail($register->id);

            if ($locked->name === $name) {
                return $locked;
            }

            $before = [
                'name' => $locked->name,
            ];

            $locked->name = $name;
            $locked->save();

            $this->audit->execute(
                eventKey: 'register.updated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: Register::class,
                subjectId: $locked->id,
                before: $before,
                after: [
                    'name' => $locked->name,
                ],
            );

            return $locked->refresh();
        });
    }
}
