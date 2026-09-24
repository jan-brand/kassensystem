<?php

use App\Modules\Hospitality\Actions\CloseHospitalityOrderAction;
use App\Modules\Hospitality\Actions\CreateDiningAreaAction;
use App\Modules\Hospitality\Actions\CreateDiningTableAction;
use App\Modules\Hospitality\Actions\OpenHospitalityOrderAction;
use App\Modules\Hospitality\Enums\HospitalityOrderStatus;
use App\Modules\Identity\Actions\CreateUserAction;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps hospitality orders separate from financial sales and allows only one open order per table', function () {
    $user = app(CreateUserAction::class)->execute(
        'hospitality-open',
        '123456',
        'Hanna',
        'Service',
    );
    $area = app(CreateDiningAreaAction::class)->execute('Innenraum', 10, $user);
    $table = app(CreateDiningTableAction::class)->execute($area, 'Tisch 1', 10, $user);

    $first = app(OpenHospitalityOrderAction::class)->execute(
        $table,
        $user,
        'Allergiehinweis am Tisch',
    );

    expect($first->number)->toMatch('/^V-\d{4}-\d{6}$/')
        ->and($first->status)->toBe(HospitalityOrderStatus::Open)
        ->and($first->open_table_id)->toBe($table->id)
        ->and($first->note)->toBe('Allergiehinweis am Tisch')
        ->and(Sale::query()->count())->toBe(0);

    expect(fn () => app(OpenHospitalityOrderAction::class)->execute($table, $user))
        ->toThrow(LogicException::class, 'bereits ein offener Vorgang');

    $closed = app(CloseHospitalityOrderAction::class)->execute($first, $user);

    expect($closed->status)->toBe(HospitalityOrderStatus::Closed)
        ->and($closed->open_table_id)->toBeNull()
        ->and($closed->closed_at)->not->toBeNull();

    $second = app(OpenHospitalityOrderAction::class)->execute($table, $user);

    expect($second->id)->not->toBe($first->id)
        ->and($second->number)->not->toBe($first->number)
        ->and(Sale::query()->count())->toBe(0);
});
