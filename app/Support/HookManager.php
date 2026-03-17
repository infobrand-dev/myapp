<?php

namespace App\Support;

class HookManager
{
    /** @var array<string, array<string, callable>> */
    private array $hooks = [];

    public function register(string $name, string $key, callable $renderer): void
    {
        $this->hooks[$name][$key] = $renderer;
    }

    public function render(string $name, array $context = []): array
    {
        $items = [];

        foreach ($this->hooks[$name] ?? [] as $renderer) {
            $output = $renderer($context);

            if (is_string($output) && trim($output) !== '') {
                $items[] = $output;
            }
        }

        return $items;
    }

    public function dispatch(string $name, array $context = []): array
    {
        $results = [];

        foreach ($this->hooks[$name] ?? [] as $renderer) {
            $results[] = $renderer($context);
        }

        return $results;
    }
}
