<?php

namespace App\Modules\Settings\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $cafeteria_name
 * @property string|null $logo_path
 * @property bool $pos_show_short_names
 * @property string|null $paypal_me_handle
 * @property int|null $updated_by_user_id
 * @property-read User|null $updatedBy
 */
final class SystemSetting extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pos_show_short_names' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
