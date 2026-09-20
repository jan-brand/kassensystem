<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\CashRegister\Actions\EnsureDefaultRegisterAction;
use App\Modules\CashRegister\Actions\RenameRegisterAction;
use App\Modules\CashRegister\Models\Register;
use App\Modules\Identity\Models\User;
use App\Modules\Settings\Actions\UpdateSystemSettingsAction;
use App\Modules\Settings\Queries\GetSystemSettingsQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.administration')]
final class SettingsScreen extends Component
{
    use WithFileUploads;

    public string $cafeteriaName = '';

    public string $registerName = '';

    public bool $posShowShortNames = true;

    public ?TemporaryUploadedFile $logoUpload = null;

    public ?string $existingLogoPath = null;

    public bool $removeLogo = false;

    public ?string $notice = null;

    public ?string $screenError = null;

    public function mount(GetSystemSettingsQuery $settingsQuery): void
    {
        $settings = $settingsQuery->execute();
        $register = Register::query()
            ->where('active', true)
            ->orderBy('id')
            ->first();

        $this->cafeteriaName = $settings?->cafeteria_name
            ?? (string) config('app.name', 'Kassensystem');
        $this->registerName = $register?->name
            ?? (string) config('kassensystem.register_name', 'Kasse 1');
        $this->posShowShortNames = $settings?->pos_show_short_names ?? true;
        $this->existingLogoPath = $settings?->logo_path;
    }

    public function save(
        UpdateSystemSettingsAction $updateSettings,
        EnsureDefaultRegisterAction $ensureRegister,
        RenameRegisterAction $renameRegister,
    ): void {
        $this->clearMessages();

        $validated = $this->validate([
            'cafeteriaName' => ['required', 'string', 'max:160'],
            'registerName' => ['required', 'string', 'max:120'],
            'posShowShortNames' => ['boolean'],
            'logoUpload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'removeLogo' => ['boolean'],
        ], [
            'cafeteriaName.required' => 'Bitte einen Namen für die Cafeteria eingeben.',
            'cafeteriaName.max' => 'Der Cafeteria-Name darf höchstens 160 Zeichen lang sein.',
            'registerName.required' => 'Bitte einen Namen für die Kasse eingeben.',
            'registerName.max' => 'Der Kassenname darf höchstens 120 Zeichen lang sein.',
            'logoUpload.image' => 'Das Logo muss eine Bilddatei sein.',
            'logoUpload.mimes' => 'Erlaubt sind JPG, PNG und WebP.',
            'logoUpload.max' => 'Das Logo darf höchstens 2 MB groß sein.',
        ]);

        $actor = $this->currentUser();
        $oldLogoPath = $this->existingLogoPath;
        $newLogoPath = null;
        $logoPath = $this->removeLogo ? null : $oldLogoPath;

        try {
            if ($this->logoUpload !== null) {
                $newLogoPath = $this->logoUpload->store('settings/logos', 'public');
                $logoPath = $newLogoPath;
            }

            DB::transaction(function () use (
                $validated,
                $actor,
                $updateSettings,
                $ensureRegister,
                $renameRegister,
                $logoPath,
            ): void {
                $updateSettings->execute(
                    actor: $actor,
                    cafeteriaName: $validated['cafeteriaName'],
                    logoPath: $logoPath,
                    posShowShortNames: $validated['posShowShortNames'],
                );

                $register = Register::query()
                    ->where('active', true)
                    ->orderBy('id')
                    ->first()
                    ?? $ensureRegister->execute($actor);

                $renameRegister->execute(
                    register: $register,
                    name: $validated['registerName'],
                    actor: $actor,
                );
            });

            if (
                $oldLogoPath !== null
                && $oldLogoPath !== $logoPath
                && str_starts_with($oldLogoPath, 'settings/logos/')
            ) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            $this->existingLogoPath = $logoPath;
            $this->logoUpload = null;
            $this->removeLogo = false;
            $this->notice = 'Einstellungen wurden gespeichert.';
        } catch (Throwable $e) {
            if ($newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }

            report($e);
            $this->screenError = 'Die Einstellungen konnten nicht gespeichert werden.';
        }
    }

    public function render(GetSystemSettingsQuery $settingsQuery): View
    {
        $settings = $settingsQuery->execute()?->load('updatedBy');

        return view('surfaces.administration.settings', [
            'settings' => $settings,
            'logoUrl' => $this->existingLogoPath !== null
                ? Storage::disk('public')->url($this->existingLogoPath)
                : null,
            'currency' => (string) config('kassensystem.currency', 'EUR'),
            'timezone' => (string) config('kassensystem.timezone', 'Europe/Berlin'),
        ]);
    }

    private function currentUser(): User
    {
        return User::query()->findOrFail(Auth::id());
    }

    private function clearMessages(): void
    {
        $this->notice = null;
        $this->screenError = null;
    }
}
