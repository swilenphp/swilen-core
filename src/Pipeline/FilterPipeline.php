<?php

namespace Swilen\Pipeline;

use Swilen\Container\Container;


class FilterPipeline
{
    /**
     * The container instance to resolve class-based filters.
     */
    protected Container $container;

    /**
     * The registered filters grouped by name and priority.
     * Format: [ 'hook.name' => [ 10 => [callback1, callback2] ] ]
     */
    protected array $filters = [];

    /**
     * Cached version of flattened and sorted filters.
     */
    protected array $sorted = [];

    /**
     * The stack of filters currently being executed.
     */
    protected array $stack = [];

    /**
     * How many times each filter hook has been applied.
     */
    protected array $firedCounts = [];

    /**
     * Create a new FilterPipeline instance.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a callback to a specific filter hook.
     *
     * @param string $name     The name of the filter hook.
     * @param mixed  $callback Callable, array [$invocable, 'method'], or string 'Class@method'.
     * @param int    $priority Execution order (lower numbers execute first).
     */
    public function add(string $name, mixed $callback, int $priority = 10): self
    {
        $this->filters[$name][$priority][] = $callback;
        
        // Invalidate cache for this hook since a new listener was added
        unset($this->sorted[$name]);
        
        return $this;
    }

    /**
     * Apply the filters for a given hook to the provided value.
     *
     * @param string $name  The name of the filter hook.
     * @param mixed  $value The initial value to be transformed.
     * @param mixed  ...$args Additional context arguments (read-only for filters).
     * 
     * @return mixed The final transformed value.
     */
    public function apply(string $name, mixed $value, ...$args): mixed
    {
        $this->stack[] = $name;
        $this->firedCounts[$name] = ($this->firedCounts[$name] ?? 0) + 1;

        $callbacks = $this->getSortedFilters($name);

        foreach ($callbacks as $callback) {
            $value = $this->dispatch($callback, $value, $args);
        }

        array_pop($this->stack);

        return $value;
    }

    /**
     * Resolve and execute the filter callback.
     *
     * @param mixed $callback
     * @param mixed $value
     * @param array $args
     * 
     * @return mixed
     */
    protected function dispatch(mixed $callback, mixed $value, array $args): mixed
    {
        // Case 1: Standard callables (Closures, invokable objects)
        if (is_callable($callback)) {
            return $callback($value, ...$args);
        }

        // Case 2: String resolution (e.g., 'App\Filters\Sanitize@handle')
        if (is_string($callback)) {
            [$class, $method] = $this->parseCallback($callback);
            
            $instance = $this->container->make($class);
            return $instance->{$method}($value, ...$args);
        }

        // Case 3: Array callables [Object, 'method']
        if (is_array($callback) && is_callable($callback)) {
            return call_user_func_array($callback, array_merge([$value], $args));
        }

        return $value;
    }

    /**
     * Get the sorted list of filters for a given hook.
     * 
     * @param string $name
     * 
     * @return array
     */
    protected function getSortedFilters(string $name): array
    {
        if (isset($this->sorted[$name])) {
            return $this->sorted[$name];
        }

        if (empty($this->filters[$name])) {
            return [];
        }

        // Sort by priority key (ASC)
        ksort($this->filters[$name]);

        // Flatten the multi-dimensional priority array into a single list
        return $this->sorted[$name] = array_merge(...$this->filters[$name]);
    }

    /**
     * Remove a specific callback or all callbacks from a hook.
     */
    public function forget(string $name, mixed $callback = null, int $priority = 10): void
    {
        if (!isset($this->filters[$name])) return;

        unset($this->sorted[$name]);

        if ($callback === null) {
            unset($this->filters[$name]);
            return;
        }

        if (isset($this->filters[$name][$priority])) {
            $this->filters[$name][$priority] = array_filter(
                $this->filters[$name][$priority], 
                fn($item) => $item !== $callback
            );
        }
    }

    /**
     * Get the name of the filter currently being executed.
     */
    public function current(): string
    {
        return end($this->stack) ?: '';
    }

    /**
     * Parse the callback string into class and method.
     */
    protected function parseCallback(string $callback): array
    {
        $segments = preg_split('/[@:]/', $callback, 2);
        return [$segments[0], $segments[1] ?? 'handle'];
    }
}