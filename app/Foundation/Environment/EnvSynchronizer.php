<?php

namespace App\Foundation\Environment;

final class EnvSynchronizer
{
    /** @return array<string, string> */
    public function parseValues(string $content): array
    {
        $values = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                $values[$matches[1]] = $matches[2];
            }
        }

        return $values;
    }

    /**
     * @return array{
     *     content: string,
     *     missing: list<string>,
     *     extra: list<string>,
     *     changed: bool
     * }
     */
    public function synchronize(
        string $templateContent,
        string $targetContent,
        bool $force = false,
        bool $prune = false,
    ): array {
        $templateValues = $this->parseValues($templateContent);
        $targetValues = $this->parseValues($targetContent);
        $lines = [];
        $seen = [];

        foreach (preg_split('/\R/', rtrim($templateContent, "\r\n")) ?: [] as $line) {
            if (preg_match('/^([A-Z][A-Z0-9_]*)=(.*)$/', $line, $matches)) {
                $key = $matches[1];
                $seen[$key] = true;
                $value = $force
                    ? $templateValues[$key]
                    : ($targetValues[$key] ?? $templateValues[$key]);

                $lines[] = $key.'='.$value;

                continue;
            }

            $lines[] = $line;
        }

        $extras = array_diff_key($targetValues, $seen);

        if (! $prune && $extras !== []) {
            $lines[] = '';
            $lines[] = '# Extra variables preserved by env:sync';

            foreach ($extras as $key => $value) {
                $lines[] = $key.'='.$value;
            }
        }

        $result = implode(PHP_EOL, $lines).PHP_EOL;

        return [
            'content' => $result,
            'missing' => array_values(array_keys(array_diff_key($templateValues, $targetValues))),
            'extra' => array_values(array_keys(array_diff_key($targetValues, $templateValues))),
            'changed' => $result !== $targetContent,
        ];
    }

    /** @return array{missing: list<string>, extra: list<string>} */
    public function diff(string $templateContent, string $targetContent): array
    {
        $template = $this->parseValues($templateContent);
        $target = $this->parseValues($targetContent);

        return [
            'missing' => array_values(array_keys(array_diff_key($template, $target))),
            'extra' => array_values(array_keys(array_diff_key($target, $template))),
        ];
    }
}
