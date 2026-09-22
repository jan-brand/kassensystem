<?php

namespace App\Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @property int $id
 * @property string $event_key
 * @property int|null $actor_user_id
 * @property string|null $actor_username
 * @property string|null $actor_display_name
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property array<array-key, mixed>|null $before
 * @property array<array-key, mixed>|null $after
 * @property array<array-key, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 */
final class AuditEvent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new LogicException('Audit events are immutable.');
        });

        static::deleting(static function (): never {
            throw new LogicException('Audit events cannot be deleted.');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}