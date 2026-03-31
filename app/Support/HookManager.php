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
            try {
                $output = $renderer($context);

                if (is_string($output) && trim($output) !== '') {
                    $items[] = $output;
                }
            } catch (\Throwable $e) {
                // One broken hook must not crash the entire page.
                if (config('app.debug')) {
                    $items[] = '<!-- hook error: ' . e($e->getMessage()) . ' -->';
                }
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
