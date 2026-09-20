<?php

namespace App\Modules\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

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