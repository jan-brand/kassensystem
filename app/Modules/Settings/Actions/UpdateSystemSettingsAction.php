<?php

namespace App\Modules\Settings\Actions;

use App\Modules\Audit\Actions\WriteAuditEventAction;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\AuthorizationService;
use App\Modules\Settings\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class UpdateSystemSettingsAction
{
    public function __construct(
        private readonly WriteAuditEventAction $audit,
        private readonly AuthorizationService $authorization,
    ) {}

    public function execute(
        User $actor,
        string $cafeteriaName,
        ?string $logoPath = null,
        bool $posShowShortNames = true,
        ?string $paypalMeHandle = null,
        ?int $receiptRetentionDays = null,
    ): SystemSetting {
        $this->authorization->authorize($actor, Permission::SettingsManage);

        $cafeteriaName = trim($cafeteriaName);
        $logoPath = $logoPath !== null ? trim($logoPath) : null;
        $paypalMeHandle = $paypalMeHandle !== null ? trim($paypalMeHandle) : null;

        if ($cafeteriaName === '') {
            throw new InvalidArgumentException('Cafeteria name must not be empty.');
        }

        if (mb_strlen($cafeteriaName) > 160) {
            throw new InvalidArgumentException('Cafeteria name must not be longer than 160 characters.');
        }

        if ($logoPath !== null && mb_strlen($logoPath) > 255) {
            throw new InvalidArgumentException('Logo path must not be longer than 255 characters.');
        }

        if ($paypalMeHandle !== null && $paypalMeHandle !== '' && ! preg_match('/^[A-Za-z0-9]{1,20}$/D', $paypalMeHandle)) {
            throw new InvalidArgumentException(
                'Der PayPal.me-Name darf nur Buchstaben und Zahlen enthalten und höchstens 20 Zeichen lang sein.',
            );
        }

        if ($receiptRetentionDays !== null && ($receiptRetentionDays < 1 || $receiptRetentionDays > 3650)) {
            throw new InvalidArgumentException(
                'Die Aufbewahrungsdauer für digitale Belege muss zwischen 1 und 3650 Tagen liegen.',
            );
        }

        if ($logoPath === '') {
            $logoPath = null;
        }

        return DB::transaction(function () use (
            $actor,
            $cafeteriaName,
            $logoPath,
            $posShowShortNames,
            $paypalMeHandle,
            $receiptRetentionDays,
        ): SystemSetting {
            $settings = SystemSetting::query()->lockForUpdate()->find(1);
            $before = $settings?->only([
                'cafeteria_name',
                'logo_path',
                'pos_show_short_names',
                'paypal_me_handle',
                'receipt_retention_days',
            ]) ?? [];
            $effectivePaypalMeHandle = $paypalMeHandle === null
                ? $settings?->paypal_me_handle
                : ($paypalMeHandle === '' ? null : $paypalMeHandle);
            $effectiveReceiptRetentionDays = $receiptRetentionDays
                ?? $settings->receipt_retention_days
                ?? 365;

            if ($settings === null) {
                $settings = new SystemSetting;
                $settings->id = 1;
            }

            $settings->fill([
                'cafeteria_name' => $cafeteriaName,
                'logo_path' => $logoPath,
                'pos_show_short_names' => $posShowShortNames,
                'paypal_me_handle' => $effectivePaypalMeHandle,
                'receipt_retention_days' => $effectiveReceiptRetentionDays,
                'updated_by_user_id' => $actor->id,
            ]);
            $settings->save();

            $this->audit->execute(
                eventKey: 'settings.updated',
                actorUserId: $actor->id,
                actorUsername: $actor->username,
                actorDisplayName: $actor->auditDisplayName(),
                subjectType: SystemSetting::class,
                subjectId: $settings->id,
                before: $before,
                after: $settings->only([
                    'cafeteria_name',
                    'logo_path',
                    'pos_show_short_names',
                    'paypal_me_handle',
                    'receipt_retention_days',
                ]),
            );

            return $settings->refresh();
        });
    }
}
