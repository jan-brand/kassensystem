<?php

namespace App\Modules\Audit\Actions;

use App\Modules\Audit\Models\AuditEvent;
use InvalidArgumentException;

final class WriteAuditEventAction
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, mixed> $metadata
     */
    public function execute(
        string $eventKey,
        ?int $actorUserId = null,
        ?string $actorUsername = null,
        ?string $actorDisplayName = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditEvent {
        $eventKey = trim($eventKey);

        if ($eventKey === '') {
            throw new InvalidArgumentException(
                'Audit event key must not be empty.'
            );
        }

        return AuditEvent::create([
            'event_key' => $eventKey,

            'actor_user_id' => $actorUserId,
            'actor_username' => $actorUsername,
            'actor_display_name' => $actorDisplayName,

            'subject_type' => $subjectType,
            'subject_id' => $subjectId,

            'before' => $this->nullableArray(
                $this->sanitize($before)
            ),
            'after' => $this->nullableArray(
                $this->sanitize($after)
            ),
            'metadata' => $this->nullableArray(
                $this->sanitize($metadata)
            ),

            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $sensitiveKeys = [
            'pin',
            'pin_hash',
            'password',
            'password_confirmation',
            'token',
            'remember_token',
            'secret',
            'api_key',
            'authorization',
        ];

        foreach ($data as $key => $value) {
            if (
                is_string($key)
                && in_array(
                    strtolower($key),
                    $sensitiveKeys,
                    true
                )
            ) {
                $data[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function nullableArray(array $data): ?array
    {
        return $data === [] ? null : $data;
    }
}