<?php

namespace App\Foundation\Architecture;

use App\Foundation\Registry\ProjectRegistry;

final class ArchitectureInspector
{
    public function __construct(private readonly ProjectRegistry $registry) {}

    public function graph(): array
    {
        $graph = [];
        foreach ($this->registry->modules() as $module) {
            $graph[$module['name']] = array_values($module['depends_on'] ?? []);
        }
        return $graph;
    }

    public function cycles(): array
    {
        $graph = $this->graph();
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
            foreach ($graph[$node] ?? [] as $dependency) { $visit($dependency, $path); }
        };
        foreach (array_keys($graph) as $node) { $visit($node, []); }
        return array_values($cycles);
    }

    public function problems(): array
    {
        $problems = [];
        $names = array_keys($this->graph());
        foreach ($this->graph() as $module => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (! in_array($dependency, $names, true)) { $problems[] = "{$module} depends on missing module {$dependency}."; }
            }
        }
        foreach ($this->cycles() as $cycle) { $problems[] = 'Circular module dependency: '.implode(' -> ', $cycle); }
        return $problems;
    }
}
