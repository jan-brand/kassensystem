<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\UserRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $username
 * @property string $pin_hash
 * @property string $first_name
 * @property string $last_name
 * @property string|null $display_name
 * @property string|null $email
 * @property string|null $phone
 * @property UserRole $role
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $last_login_at
 * @property \Illuminate\Support\Carbon|null $pin_changed_at
 */
final class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'pin_hash',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'pin_changed_at' => 'datetime',
        ];
    }

    public function auditDisplayName(): string
    {
        return $this->display_name
            ?: trim($this->first_name.' '.$this->last_name)
            ?: $this->username;
    }
}
