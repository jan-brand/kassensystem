<?php

namespace App\Foundation\Design;

use App\Foundation\Registry\ProjectRegistry;

final class DesignInspector
{
    public function __construct(private readonly ProjectRegistry $registry) {}

    public function problems(): array
    {
        $items = $this->registry->design();
        $byName = [];
        foreach ($items as $item) { $byName[$item['name'] ?? ''] = $item; }
        $rank = ['component' => 1, 'pattern' => 2, 'template' => 3];
        $problems = [];
        foreach ($items as $item) {
            $type = $item['type'] ?? '';
            foreach ($item['uses'] ?? [] as $used) {
                if (! isset($byName[$used])) { $problems[] = "{$item['name']} uses missing design item {$used}."; continue; }
                $usedType = $byName[$used]['type'] ?? '';
                if (($rank[$usedType] ?? 99) > ($rank[$type] ?? 0) || ($type === 'template' && $usedType === 'template')) {
                    $problems[] = "Invalid design dependency: {$item['name']} ({$type}) -> {$used} ({$usedType}).";
                }
            }
        }
        foreach ($this->cycles() as $cycle) { $problems[] = 'Circular design dependency: '.implode(' -> ', $cycle); }
        return $problems;
    }

    public function cycles(): array
    {
        $graph = [];
        foreach ($this->registry->design() as $item) { $graph[$item['name']] = $item['uses'] ?? []; }
        $cycles = [];
        $visit = function (string $node, array $path) use (&$visit, &$cycles, $graph): void {
            if (in_array($node, $path, true)) {
                $start = array_search($node, $path, true);
                $cycle = array_slice($path, $start);
                $cycle[] = $node;
                $cycles[implode('>', $cycle)] = $cycle;
                return;
            }
            $path[] = $node;
            foreach ($graph[$node] ?? [] as $next) { if (isset($graph[$next])) { $visit($next, $path); } }
        };
        foreach (array_keys($graph) as $node) { $visit($node, []); }
        return array_values($cycles);
    }

    public function usage(string $name): array
    {
        $users = [];
        foreach ($this->registry->design() as $item) {
            if (in_array($name, $item['uses'] ?? [], true)) { $users[] = $item['name']; }
        }
        return $users;
    }
}
