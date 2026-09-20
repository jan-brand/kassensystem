<?php

namespace App\Modules\Identity\Models;

use App\Modules\Identity\Enums\UserRole;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

final class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'pin_hash',
    ];

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
