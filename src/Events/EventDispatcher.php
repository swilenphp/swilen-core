<?php

namespace Swilen\Events;

use Swilen\Shared\Container\Container;

class EventDispatcher implements Dispatcher
{
    /**
     * Registered listeners grouped by event and priority.
     *
     * @var array<string, array<int, array<mixed>>>
     */
    protected array $listeners = [];

    /**
     * Rebuilt listeners stack (caches both exact and wildcard listeners).
     *
     * @var array<string, array<mixed>>
     */
    protected array $sorted = [];

    /**
     * The stack of events currently being executed.
     *
     * @var array<string>
     */
    protected array $stack = [];

    /**
     * @var array<string,int>
     */
    protected array $firedCounts = [];

    /**
     * Create a new dispatcher instance.
     *
     * @param Container|null $container
     * @param Queue|null $queue
     */
    public function __construct(
        protected ?Container $container = null,
        protected ?Queue $queue = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listen(string $event, mixed $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;

        if (str_contains($event, '*')) {
            $this->sorted = [];
        } else {
            unset($this->sorted[$event]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(string $event, mixed $listener, int $priority = 0): void
    {
        $this->listen($event, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object|string $event, array $payload = []): void
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        $listeners = $this->getSortedListeners($eventName);

        $this->firedCounts[$eventName] = ($this->firedCounts[$eventName] ?? 0) + 1;

        if (empty($listeners)) {
            return;
        }

        // Check if the event should be unpacked.
        $shouldUnpack = is_object($event) && method_exists($event, 'shouldUnpack') && $event->shouldUnpack();

        foreach ($listeners as $listener) {
            $handler = function () use ($listener, $event, $shouldUnpack, $eventName, $payload) {
                $this->stack[] = $eventName;

                $params = [is_object($event) ? $event : $payload];
                if ($shouldUnpack && is_object($event) && method_exists($event, 'payload')) {
                    try {
                        $params = array_values($event->payload());
                    } catch (\Throwable $e) {
                        throw new \LogicException('Event payload could not be retrieved.', 0, $e);
                    }
                }

                $resolved = $this->resolveListener($listener);

                $result = $resolved(...$params);

                array_pop($this->stack);

                return $result;
            };

            if ($this->queue && !$shouldUnpack) {
                $this->queue->push($handler);
            } else {
                $handler();
            }
        }
    }

    /**
     * Check if an event has listeners (including wildcards).
     */
    public function has(string $event): bool
    {
        return !empty($this->getSortedListeners($event));
    }

    /**
     * Get the number of times an event has fired.
     */
    public function firedCount(string $event): int
    {
        return $this->firedCounts[$event] ?? 0;
    }

    /**
     * Forget an event listener.
     */
    public function forget(string $event, mixed $callback = null, int $priority = 0): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        // Clear cache
        unset($this->sorted[$event]);

        if ($callback === null) {
            unset($this->listeners[$event]);
            return;
        }

        if (isset($this->listeners[$event][$priority])) {
            foreach ($this->listeners[$event][$priority] as $index => $registered) {
                if ($registered === $callback) {
                    unset($this->listeners[$event][$priority][$index]);
                }
            }
        }
    }

    /**
     * Get current event.
     */
    public function current(): string
    {
        return end($this->stack) ?: '';
    }

    /**
     * Rebuilds the priority stack combining exact matches and wildcards.
     */
    protected function getSortedListeners(string $eventName): array
    {
        if (isset($this->sorted[$eventName])) {
            return $this->sorted[$eventName];
        }

        $matchedListeners = [];

        foreach ($this->listeners as $registeredEvent => $priorities) {
            if ($registeredEvent === $eventName || $this->strIs($registeredEvent, $eventName)) {
                foreach ($priorities as $priority => $listeners) {
                    foreach ($listeners as $listener) {
                        $matchedListeners[$priority][] = $listener;
                    }
                }
            }
        }

        if (empty($matchedListeners)) {
            return $this->sorted[$eventName] = [];
        }

        ksort($matchedListeners);

        $flattened = [];
        foreach ($matchedListeners as $group) {
            foreach ($group as $listener) {
                $flattened[] = $listener;
            }
        }

        return $this->sorted[$eventName] = $flattened;
    }

    /**
     * Resolves a string or array listener via the Container.
     */
    protected function resolveListener(mixed $listener): callable
    {
        $container = $this->container;

        if (is_callable($listener)) {
            return function (...$args) use ($listener, $container) {
                return $container ? $container->call($listener, $args) : $listener(...$args);
            };
        }

        if (is_string($listener)) {
            return function (...$args) use ($listener, $container) {
                return $container ? $container->make($listener, $args) : new $listener(...$args);
            };
        }

        if (is_array($listener) && is_string($listener[0])) {
            return function (...$args) use ($listener, $container) {
                $instance = $container ? $container->make($listener[0], $args) : new $listener[0](...$args);
                $method = $listener[1] ?? 'handle';

                return $container ? $container->callMethod($instance, $method, $args) : $instance->{$method}(...$args);
            };
        }

        throw new \InvalidArgumentException('Invalid listener type provided.');
    }

    /**
     * Determine if a given string matches a given pattern (Wildcard support).
     */
    protected function strIs(string $pattern, string $value): bool
    {
        if ($pattern === $value) {
            return true;
        }

        $pattern = preg_quote($pattern, '#');
        // Replace wildcard \* with .*
        $pattern = str_replace('\*', '.*', $pattern);

        return preg_match('#^' . $pattern . '\z#u', $value) === 1;
    }
}
