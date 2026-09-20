<?php

namespace App\Modules\Identity\Console\Commands;

use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Identity\Enums\UserRole;
use Illuminate\Console\Command;
use Throwable;

final class CreateAdminCommand extends Command
{
    protected $signature = 'identity:create-admin
        {username? : Login name for the administrator}
        {--first-name= : First name}
        {--last-name= : Last name}
        {--pin= : Six digit PIN; omit to enter it securely}';

    protected $description = 'Create the initial administrator account.';

    public function handle(CreateUserAction $createUser): int
    {
        $username = (string) ($this->argument('username') ?: $this->ask('Username'));
        $firstName = (string) ($this->option('first-name') ?: $this->ask('First name'));
        $lastName = (string) ($this->option('last-name') ?: $this->ask('Last name'));
        $pin = (string) ($this->option('pin') ?: $this->secret('Six digit PIN'));

        try {
            $user = $createUser->execute(
                username: $username,
                pin: $pin,
                firstName: $firstName,
                lastName: $lastName,
                role: UserRole::Administrator,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Administrator {$user->username} created.");

        return self::SUCCESS;
    }
}
