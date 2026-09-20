<?php

namespace App\Modules\Settings\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SystemSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'pos_show_short_names' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
