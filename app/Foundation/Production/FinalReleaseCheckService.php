<?php

namespace App\Foundation\Production;

use JsonException;

/**
 * @phpstan-type FinalCheckResult array{
 *     name: string,
 *     status: 'pass'|'fail',
 *     message: string
 * }
 */
final class FinalReleaseCheckService
{
    /**
     * @return list<FinalCheckResult>
     */
    public function run(): array
    {
        return [
            $this->releaseMetadataCheck(),
            $this->manualAcceptanceCheck(),
            $this->issueStatusCheck('V1-008', 'Acceptance issue'),
            $this->issueStatusCheck('V1-000', 'v1 epic'),
            $this->blockingIssuesCheck(),
            $this->changelogCheck(),
        ];
    }

    /**
     * @return list<string>
     */
    public function blockers(): array
    {
        return array_values(array_map(
            static fn (array $result): string => $result['message'],
            array_filter(
                $this->run(),
                static fn (array $result): bool => $result['status'] === 'fail',
            ),
        ));
    }

    public function isReady(): bool
    {
        return $this->blockers() === [];
    }

    /** @return FinalCheckResult */
    private function releaseMetadataCheck(): array
    {
        $version = (string) config('release.version');
        $target = (string) config('release.target_version');
        $stage = (string) config('release.stage');

        $ready = $stage === 'final'
            && $version === $target
            && $target === '1.0.0';

        return $this->result(
            'Final release metadata',
            $ready ? 'pass' : 'fail',
            $ready
                ? "Release metadata targets {$target} as final."
                : "Current release is {$version} ({$stage}); promote it to {$target} only after manual acceptance.",
        );
    }

    /** @return FinalCheckResult */
    private function manualAcceptanceCheck(): array
    {
        $path = (string) config(
            'release.acceptance_record',
            base_path('release/v1-acceptance.json'),
        );
        $record = $this->readJson($path);

        if ($record === null) {
            return $this->result(
                'Manual acceptance',
                'fail',
                'The v1 acceptance record is missing or invalid.',
            );
        }

        if (
            ($record['release'] ?? null) !== config('release.version')
            || ($record['target'] ?? null) !== config('release.target_version')
        ) {
            return $this->result(
                'Manual acceptance',
                'fail',
                'The acceptance record does not match the configured release metadata.',
            );
        }

        if (($record['status'] ?? null) !== 'passed') {
            return $this->result(
                'Manual acceptance',
                'fail',
                'Manual v1 acceptance is still pending.',
            );
        }

        foreach (['date', 'tester', 'commit'] as $field) {
            $value = $record[$field] ?? null;

            if (! is_string($value) || trim($value) === '') {
                return $this->result(
                    'Manual acceptance',
                    'fail',
                    "Acceptance field {$field} must be documented.",
                );
            }
        }

        $checks = $record['checks'] ?? null;

        if (! is_array($checks)) {
            return $this->result(
                'Manual acceptance',
                'fail',
                'Manual acceptance checks are missing.',
            );
        }

        $required = [
            'manual_checklist_complete',
            'desktop_browser',
            'smartphone_or_tablet',
            'backup_restore',
            'production_check_reviewed',
        ];

        $missing = array_values(array_filter(
            $required,
            static fn (string $name): bool => ($checks[$name] ?? false) !== true,
        ));

        if ($missing !== []) {
            return $this->result(
                'Manual acceptance',
                'fail',
                'Incomplete manual checks: '.implode(', ', $missing),
            );
        }

        return $this->result(
            'Manual acceptance',
            'pass',
            'Manual v1 acceptance is fully documented.',
        );
    }

    /** @return FinalCheckResult */
    private function issueStatusCheck(string $id, string $name): array
    {
        $data = $this->readJson(base_path('issues/issues.json'));
        $issues = $data['issues'] ?? null;

        if (! is_array($issues)) {
            return $this->result(
                $name,
                'fail',
                'The local issue registry is missing or invalid.',
            );
        }

        foreach ($issues as $issue) {
            if (
                ! is_array($issue)
                || ($issue['id'] ?? null) !== $id
            ) {
                continue;
            }

            $closed = ($issue['status'] ?? null) === 'closed';

            return $this->result(
                $name,
                $closed ? 'pass' : 'fail',
                $closed
                    ? "{$id} is closed."
                    : "{$id} must be closed before v1.0.0.",
            );
        }

        return $this->result(
            $name,
            'fail',
            "{$id} is missing from the local issue registry.",
        );
    }

    /** @return FinalCheckResult */
    private function blockingIssuesCheck(): array
    {
        $data = $this->readJson(base_path('issues/issues.json'));
        $issues = $data['issues'] ?? null;

        if (! is_array($issues)) {
            return $this->result(
                'Blocking issues',
                'fail',
                'The local issue registry is missing or invalid.',
            );
        }

        $blockers = [];

        foreach ($issues as $issue) {
            if (! is_array($issue)) {
                continue;
            }

            $id = $issue['id'] ?? null;
            $status = $issue['status'] ?? null;
            $priority = $issue['priority'] ?? null;

            if (
                ! is_string($id)
                || $status !== 'open'
                || ! in_array($priority, ['P0', 'P1'], true)
                || in_array($id, ['V1-000', 'V1-008'], true)
            ) {
                continue;
            }

            $blockers[] = $id;
        }

        return $this->result(
            'Blocking issues',
            $blockers === [] ? 'pass' : 'fail',
            $blockers === []
                ? 'No additional open P0/P1 issues remain.'
                : 'Open P0/P1 issue(s): '.implode(', ', $blockers),
        );
    }

    /** @return FinalCheckResult */
    private function changelogCheck(): array
    {
        $version = (string) config('release.version');
        $path = base_path('CHANGELOG.md');

        if (! is_file($path)) {
            return $this->result(
                'Changelog',
                'fail',
                'CHANGELOG.md is missing.',
            );
        }

        $content = (string) file_get_contents($path);
        $documented = str_contains(
            $content,
            "## {$version} -",
        );

        return $this->result(
            'Changelog',
            $documented ? 'pass' : 'fail',
            $documented
                ? "CHANGELOG.md documents {$version}."
                : "CHANGELOG.md must document {$version}.",
        );
    }

    /**
     * @return array<array-key, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            $decoded = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return null;
        }

        if (! is_array($decoded)) {
            return null;
        }

        /** @var array<array-key, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param  'pass'|'fail'  $status
     * @return FinalCheckResult
     */
    private function result(
        string $name,
        string $status,
        string $message,
    ): array {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
        ];
    }
}
