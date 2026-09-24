<?php

namespace App\Surfaces\Administration\Livewire;

use App\Modules\Hospitality\Models\DiningArea;
use App\Modules\Hospitality\Models\DiningTable;
use App\Modules\Hospitality\Services\HospitalityConfigurationService;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use LogicException;
use Throwable;

#[Layout('layouts.administration')]
final class HospitalityConfigurationScreen extends Component
{
    public string $areaName = '';

    public int $areaSortOrder = 0;

    public ?int $tableAreaId = null;

    public string $tableName = '';

    public int $tableSortOrder = 0;

    public ?int $editingAreaId = null;

    public string $editAreaName = '';

    public int $editAreaSortOrder = 0;

    public ?int $editingTableId = null;

    public ?int $editTableAreaId = null;

    public string $editTableName = '';

    public int $editTableSortOrder = 0;

    public ?string $notice = null;

    public ?string $screenError = null;

    public function boot(): void
    {
        Gate::authorize(Permission::HospitalityConfigurationManage->value);
    }

    public function createArea(HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        try {
            $service->createArea($this->currentUser(), $this->areaName, $this->areaSortOrder);
            $this->areaName = '';
            $this->areaSortOrder = 0;
            $this->notice = 'Bereich wurde angelegt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function createTable(HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        try {
            if ($this->tableAreaId === null) {
                throw new InvalidArgumentException('Bitte einen Bereich auswählen.');
            }

            $service->createTable(
                $this->currentUser(),
                DiningArea::query()->findOrFail($this->tableAreaId),
                $this->tableName,
                $this->tableSortOrder,
            );

            $this->tableName = '';
            $this->tableSortOrder = 0;
            $this->notice = 'Tisch wurde angelegt.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function startEditArea(int $id): void
    {
        $area = DiningArea::query()->findOrFail($id);
        $this->editingAreaId = $area->id;
        $this->editAreaName = $area->name;
        $this->editAreaSortOrder = $area->sort_order;
    }

    public function saveArea(HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        if ($this->editingAreaId === null) {
            return;
        }

        try {
            $service->updateArea(
                $this->currentUser(),
                DiningArea::query()->findOrFail($this->editingAreaId),
                $this->editAreaName,
                $this->editAreaSortOrder,
            );

            $this->editingAreaId = null;
            $this->notice = 'Bereich wurde gespeichert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function toggleArea(int $id, HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        try {
            $area = DiningArea::query()->findOrFail($id);
            $service->setAreaActive($this->currentUser(), $area, ! $area->active);
            $this->notice = 'Bereichsstatus wurde geändert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function startEditTable(int $id): void
    {
        $table = DiningTable::query()->findOrFail($id);
        $this->editingTableId = $table->id;
        $this->editTableAreaId = $table->area_id;
        $this->editTableName = $table->name;
        $this->editTableSortOrder = $table->sort_order;
    }

    public function saveTable(HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        if ($this->editingTableId === null || $this->editTableAreaId === null) {
            return;
        }

        try {
            $service->updateTable(
                $this->currentUser(),
                DiningTable::query()->findOrFail($this->editingTableId),
                DiningArea::query()->findOrFail($this->editTableAreaId),
                $this->editTableName,
                $this->editTableSortOrder,
            );

            $this->editingTableId = null;
            $this->notice = 'Tisch wurde gespeichert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function toggleTable(int $id, HospitalityConfigurationService $service): void
    {
        $this->clearMessages();

        try {
            $table = DiningTable::query()->findOrFail($id);
            $service->setTableActive($this->currentUser(), $table, ! $table->active);
            $this->notice = 'Tischstatus wurde geändert.';
        } catch (Throwable $exception) {
            $this->screenError = $this->message($exception);
        }
    }

    public function render(): View
    {
        return view('surfaces.administration.hospitality', [
            'areas' => DiningArea::query()
                ->with(['tables.openOrder'])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
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

    private function message(Throwable $exception): string
    {
        if ($exception instanceof InvalidArgumentException || $exception instanceof LogicException) {
            return $exception->getMessage();
        }

        report($exception);

        return 'Die Aktion konnte nicht ausgeführt werden.';
    }
}
