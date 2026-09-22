<?php

namespace App\Modules\CashRegister\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property bool $active
 * @property-read Collection<int, CashSession> $cashSessions
 */
final class Register extends Model
{
    protected $guarded = [];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    /** @return HasMany<CashSession, $this> */
    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }
}
