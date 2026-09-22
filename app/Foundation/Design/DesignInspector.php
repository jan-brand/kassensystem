<?php

namespace App\Foundation\Design;

use App\Foundation\Registry\ProjectRegistry;

final class DesignInspector
{
    public function __construct(
        private readonly ProjectRegistry $registry,
    ) {}

    /** @return list<string> */
    public function problems(): array
    {
        $items = $this->registry->design();
        $byName = [];

        foreach ($items as $item) {
            $name = $item['name'] ?? null;

            if (is_string($name)) {
                $byName[$name] = $item;
            }
        }

        $rank = [
            'component' => 1,
            'pattern' => 2,
            'template' => 3,
        ];
        $problems = [];

        foreach ($items as $item) {
            $name = is_string($item['name'] ?? null)
                ? $item['name']
                : 'unknown';
            $type = is_string($item['type'] ?? null)
                ? $item['type']
                : '';
            $uses = is_array($item['uses'] ?? null)
                ? $item['uses']
                : [];

            foreach ($uses as $used) {
                if (! is_string($used)) {
                    continue;
                }

                if (! isset($byName[$used])) {
                    $problems[] = "{$name} uses missing design item {$used}.";

                    continue;
                }

                $usedType = is_string($byName[$used]['type'] ?? null)
                    ? $byName[$used]['type']
                    : '';

                if (
                    ($rank[$usedType] ?? 99) > ($rank[$type] ?? 0)
                    || ($type === 'template' && $usedType === 'template')
                ) {
                    $problems[] = "Invalid design dependency: {$name} ({$type}) -> {$used} ({$usedType}).";
                }
            }
        }

        foreach ($this->cycles() as $cycle) {
            $problems[] = 'Circular design dependency: '.implode(' -> ', $cycle);
        }

        return $problems;
    }

    /** @return list<list<string>> */
    public function cycles(): array
    {
        /** @var array<string, list<string>> $graph */
        $graph = [];

        foreach ($this->registry->design() as $item) {
            $name = $item['name'] ?? null;
            $uses = $item['uses'] ?? [];

            if (! is_string($name) || ! is_array($uses)) {
                continue;
            }

            $graph[$name] = array_values(array_filter(
                $uses,
                static fn (mixed $used): bool => is_string($used),
            ));
        }

        /** @var array<string, list<string>> $cycles */
        $cycles = [];

        /**
         * @param  list<string>  $path
         */
        $visit = function (string $node, array $path) use (&$visit, &$cycles, $graph): void {
            if (in_array($node, $path, true)) {
                $start = array_search($node, $path, true);

                if ($start === false) {
                    return;
                }

                $cycle = array_slice($path, $start);
                $cycle[] = $node;
                $cycles[implode('>', $cycle)] = $cycle;

                return;
            }

            $path[] = $node;

            foreach ($graph[$node] ?? [] as $next) {
                if (isset($graph[$next])) {
                    $visit($next, $path);
                }
            }
        };

        foreach (array_keys($graph) as $node) {
            $visit($node, []);
        }

        return array_values($cycles);
    }

    /** @return list<string> */
    public function usage(string $name): array
    {
        $users = [];

        foreach ($this->registry->design() as $item) {
            $itemName = $item['name'] ?? null;
            $uses = $item['uses'] ?? [];

            if (! is_string($itemName) || ! is_array($uses)) {
                continue;
            }

            if (in_array($name, $uses, true)) {
                $users[] = $itemName;
            }
        }

        return $users;
    }
}
