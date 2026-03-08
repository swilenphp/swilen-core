<?php

namespace Swilen\Pipeline;

use Swilen\Container\Container;

class PriorityPipeline
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * Registered hooks grouped by tag and priority.
     *
     * @var array<string, array<int, array<callable>>>
     */
    protected array $hooks = [];

    /**
     * Rebuilt hooks stack.
     *
     * @var array<string, array<callable>>
     */
    protected array $sorted = [];

    /**
     * Create a new pipeline instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a callback to a specific tag with priority.
     */
    public function add(string $tag, callable $callback, int $priority = 10): void
    {
        $this->hooks[$tag][$priority][] = $callback;
        unset($this->sorted[$tag]);
    }

    /**
     * Apply the filters to a value.
     */
    public function apply(string $tag, mixed $value, array $args = []): mixed
    {
        $hooks = $this->getSorted($tag);

        if (empty($hooks)) {
            return $value;
        }

        foreach ($hooks as $callback) {
            $value = $callback($value, ...$args);
        }

        return $value;
    }

    /**
     * Check if a tag has any hooks.
     */
    public function has(string $tag): bool
    {
        return !empty($this->hooks[$tag]);
    }

    /**
     * Remove a callback from a specific tag and priority.
     */
    public function remove(string $tag, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$tag][$priority])) {
            return;
        }

        foreach ($this->hooks[$tag][$priority] as $index => $registered) {
            if ($registered === $callback) {
                unset($this->hooks[$tag][$priority][$index]);
                unset($this->sorted[$tag]);
            }
        }
    }

    /**
     * Get sorted hooks for a tag.
     */
    protected function getSorted(string $tag): array
    {
        if (isset($this->sorted[$tag])) {
            return $this->sorted[$tag];
        }

        if (!isset($this->hooks[$tag])) {
            return $this->sorted[$tag] = [];
        }

        ksort($this->hooks[$tag]);

        $flattened = [];
        foreach ($this->hooks[$tag] as $group) {
            foreach ($group as $callback) {
                $flattened[] = $callback;
            }
        }

        return $this->sorted[$tag] = $flattened;
    }
}
