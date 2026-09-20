<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Identity\Enums\UserRole;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\CashierManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class CashiersScreen extends Component
{
    public string $username = '';
    public string $pin = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $email = '';
    public string $phone = '';

    public ?int $editingId = null;
    public string $editUsername = '';
    public string $editFirstName = '';
    public string $editLastName = '';
    public string $editDisplayName = '';
    public string $editEmail = '';
    public string $editPhone = '';

    public ?int $resetPinId = null;
    public string $newPin = '';

    public ?string $notice = null;
    public ?string $screenError = null;

    public function createCashier(CashierManagementService $service): void
    {
        $this->clearMessages();

        try {
            $service->create(
                actor: $this->currentUser(),
                username: $this->username,
                pin: $this->pin,
                firstName: $this->firstName,
                lastName: $this->lastName,
                email: $this->email ?: null,
                phone: $this->phone ?: null,
            );

            $this->username = $this->pin = $this->firstName = $this->lastName = '';
            $this->email = $this->phone = '';
            $this->notice = 'Kassierer wurde angelegt.';
        } catch (Throwable $e) {
            $this->pin = '';
            $this->screenError = $this->message($e);
        }
    }

    public function startEdit(int $id): void
    {
        $cashier = $this->cashier($id);
        $this->editingId = $cashier->id;
        $this->editUsername = $cashier->username;
        $this->editFirstName = $cashier->first_name;
        $this->editLastName = $cashier->last_name;
        $this->editDisplayName = $cashier->display_name ?? '';
        $this->editEmail = $cashier->email ?? '';
        $this->editPhone = $cashier->phone ?? '';
    }

    public function saveCashier(CashierManagementService $service): void
    {
        $this->clearMessages();

        if ($this->editingId === null) {
            return;
        }

        try {
            $cashier = $this->cashier($this->editingId);

            $service->update(
                actor: $this->currentUser(),
                cashier: $cashier,
                username: $this->editUsername,
                firstName: $this->editFirstName,
                lastName: $this->editLastName,
                displayName: $this->editDisplayName ?: null,
                email: $this->editEmail ?: null,
                phone: $this->editPhone ?: null,
            );

            $this->editingId = null;
            $this->notice = 'Kassierer wurde gespeichert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function toggleActive(int $id, CashierManagementService $service): void
    {
        $this->clearMessages();

        try {
            $cashier = $this->cashier($id);
            $service->setActive($this->currentUser(), $cashier, ! $cashier->active);
            $this->notice = 'Kassiererstatus wurde geändert.';
        } catch (Throwable $e) {
            $this->screenError = $this->message($e);
        }
    }

    public function startPinReset(int $id): void
    {
        $this->cashier($id);
        $this->resetPinId = $id;
        $this->newPin = '';
    }

    public function resetPin(CashierManagementService $service): void
    {
        $this->clearMessages();

        if ($this->resetPinId === null) {
            return;
        }

        try {
            $service->resetPin($this->currentUser(), $this->cashier($this->resetPinId), $this->newPin);
            $this->resetPinId = null;
            $this->newPin = '';
            $this->notice = 'PIN wurde zurückgesetzt.';
        } catch (Throwable $e) {
            $this->newPin = '';
            $this->screenError = $this->message($e);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.cashiers', [
            'cashiers' => User::query()
                ->where('role', UserRole::Cashier->value)
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

    private function cashier(int $id): User
    {
        $user = User::query()->findOrFail($id);

        if ($user->role !== UserRole::Cashier) {
            throw new LogicException('This area may only manage cashier accounts.');
        }

        return $user;
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }

    private function message(Throwable $e): string
    {
        if ($e instanceof InvalidArgumentException || $e instanceof LogicException) {
            return $e->getMessage();
        }

        report($e);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
