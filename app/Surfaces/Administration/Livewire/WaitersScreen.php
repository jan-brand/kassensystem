<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\WaiterManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class WaitersScreen extends Component
{
    public string $username = '';

    public string $pin = '';

    public string $firstName = '';

    public string $lastName = '';

    public string $displayName = '';

    public ?int $editingId = null;

    public string $editUsername = '';

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editDisplayName = '';

    public ?int $resetPinId = null;

    public string $newPin = '';

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::UsersWaitersManage->value);
    }

    public function createWaiter(WaiterManagementService $service): void
    {
        $this->clearMessages();

        try {
            $service->create(
                actor: $this->currentUser(),
                username: $this->username,
                pin: $this->pin,
                firstName: $this->firstName,
                lastName: $this->lastName,
                displayName: $this->displayName ?: null,
            );

            $this->reset(['username', 'pin', 'firstName', 'lastName', 'displayName']);
            $this->notice = 'Kellner wurde angelegt.';
        } catch (Throwable $exception) {
            $this->pin = '';
            $this->screenError = $this->message($exception);
        }
    }

    public function startEdit(int $id): void
    {
        $waiter = $this->waiter($id);

        $this->editingId = $waiter->id;
        $this->editUsername = $waiter->username;
        $this->editFirstName = $waiter->first_name;
        $this->editLastName = $waiter->last_name;
        $this->editDisplayName = $waiter->display_name ?? '';
    }

    public function saveWaiter(WaiterManagementService $service): void
    {
        $this->clearMessages();

        if ($this->editingId === null) {
            return;
        }

        try {
            $service->update(
                actor: $this->currentUser(),
                waiter: $this->waiter($this->editingId),
                username: $this->editUsername,
                firstName: $this->editFirstName,
                lastName: $this->editLastName,
                displayName: $this->editDisplayName ?: null,
            );

            $this->editingId = null;
            $this->notice = 'Kellner wurde gespeichert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function toggleActive(int $id, WaiterManagementService $service): void
    {
        $this->clearMessages();

        try {
            $waiter = $this->waiter($id);
            $service->setActive($this->currentUser(), $waiter, ! $waiter->active);
            $this->notice = 'Kellnerstatus wurde geändert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function startPinReset(int $id): void
    {
        $this->waiter($id);
        $this->resetPinId = $id;
        $this->newPin = '';
    }

    public function resetPin(WaiterManagementService $service): void
    {
        $this->clearMessages();

        if ($this->resetPinId === null) {
            return;
        }

        try {
            $service->resetPin(
                $this->currentUser(),
                $this->waiter($this->resetPinId),
                $this->newPin,
            );
            $this->resetPinId = null;
            $this->newPin = '';
            $this->notice = 'PIN wurde zurückgesetzt.';
        } catch (Throwable $exception) {
            $this->newPin = '';
            $this->screenError = $this->message($exception);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.waiters', [
            'waiters' => User::query()
                ->where('role', UserRole::Waiter->value)
                ->orderByDesc('active')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(),
        ]);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function waiter(int $id): User
    {
        $user = User::query()->findOrFail($id);

        if ($user->role !== UserRole::Waiter) {
            throw new LogicException('Dieser Bereich verwaltet ausschließlich Kellnerkonten.');
        }

        return $user;
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function message(Throwable $exception): string
    {
        if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
