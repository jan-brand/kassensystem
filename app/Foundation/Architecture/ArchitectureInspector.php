<?php

namespace App\Foundation\Architecture;

use App\Foundation\Registry\ProjectRegistry;

final class ArchitectureInspector
{
    public function __construct(
        private readonly ProjectRegistry $registry,
    ) {}

    /** @return array<string, list<string>> */
    public function graph(): array
    {
        $graph = [];

        foreach ($this->registry->modules() as $module) {
            $name = $module['name'] ?? null;
            $dependencies = $module['depends_on'] ?? [];

            if (! is_string($name) || ! is_array($dependencies)) {
                continue;
            }

            $graph[$name] = array_values(array_filter(
                $dependencies,
                static fn (mixed $dependency): bool => is_string($dependency),
            ));
        }

        return $graph;
    }

    /** @return list<list<string>> */
    public function cycles(): array
    {
        $graph = $this->graph();

        /** @var array<string, list<string>> $cycles */
        $cycles = [];

        /**
         * @param list<string> $path
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

            foreach ($graph[$node] ?? [] as $dependency) {
                $visit($dependency, $path);
            }
        };

        foreach (array_keys($graph) as $node) {
            $visit($node, []);
        }

        return array_values($cycles);
    }

    /** @return list<string> */
    public function problems(): array
    {
        $graph = $this->graph();
        $problems = [];
        $names = array_keys($graph);

        foreach ($graph as $module => $dependencies) {
            foreach ($dependencies as $dependency) {
                if (! in_array($dependency, $names, true)) {
                    $problems[] = "{$module} depends on missing module {$dependency}.";
                }
            }
        }

        foreach ($this->cycles() as $cycle) {
            $problems[] = 'Circular module dependency: '.implode(' -> ', $cycle);
        }

        return $problems;
    }
}
