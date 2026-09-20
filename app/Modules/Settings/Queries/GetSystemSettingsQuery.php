<?php

namespace App\Modules\Settings\Queries;

use App\Modules\Settings\Models\SystemSetting;

final class GetSystemSettingsQuery
{
    public function execute(): ?SystemSetting
    {
        return SystemSetting::query()->find(1);
    }
}
