<?php

namespace Swilen\Events;

use Swilen\Wp\Hooks\WpHookEvent;

class EventDispatcher implements Dispatcher
{
    /**
     * Registered listeners grouped by event and priority.
     *
     * Structure:
     *
     * [
     *     'event.name' => [
     *         0 => [listener, listener],
     *         10 => [listener]
     *     ]
     * ]
     *
     * @var array<string, array<int, array<callable|Listener>>>
     */
    protected array $listeners = [];

    /**
     * Rebuilt listeners stack.
     *
     * @var array<string, array<callable|Listener>>
     */
    protected array $sorted = [];

    /**
     * Optional queue for async listener execution.
     */
    protected ?Queue $queue = null;

    /**
     * @var array<string,int>
     */
    protected array $firedCounts = [];

    /**
     * Create a new dispatcher instance.
     */
    public function __construct(?Queue $queue = null)
    {
        $this->queue = $queue;
    }

    /**
     * Register an event listener.
     */
    public function listen(string $event, callable|Listener $listener, int $priority = 0): void
    {
        $this->listeners[$event][$priority][] = $listener;

        // Invalidate sorted listeners.
        unset($this->sorted[$event]);
    }

    /**
     * The stack of hooks currently being executed.
     *
     * @var array<string>
     */
    protected array $stack = [];

    /**
     * Dispatch an event.
     */
    /**
     * Dispatch an event.
     */
    public function dispatch(Event|string $event, array $payload = []): void
    {
        $eventName = is_string($event) ? $event : $event->name();

        if (is_string($event)) {
            $event = new class($eventName, $payload) implements Event {
                public function __construct(
                    protected string $name,
                    protected array $payload
                ) {}

                public function name(): string
                {
                    return $this->name;
                }

                public function payload(): array
                {
                    return $this->payload;
                }
            };
        }

        $listeners = $this->getSortedListeners($eventName);
        $this->firedCounts[$eventName] = ($this->firedCounts[$eventName] ?? 0) + 1;

        if (empty($listeners)) {
            return;
        }

        $isWpEvent = $event instanceof WpHookEvent;

        foreach ($listeners as $listener) {
            if ($listener instanceof Listener) {
                $handler = $isWpEvent ? fn() => $listener->handle(...$event->payload()) : fn() => $listener->handle($event);
            } else {
                $handler = $isWpEvent ? fn() => $listener(...$event->payload()) : fn() => $listener($event);
            }

            if ($this->queue && !$isWpEvent) {
                $this->queue->push($handler);
            } else {
                $handler();
            }
        }
    }

    /**
     * Check if an event has listeners.
     */
    public function has(string $event): bool
    {
        return !empty($this->listeners[$event]);
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
    public function forget(string $event, ?callable $callback = null, int $priority = 0): void
    {
        if (!isset($this->listeners[$event][$priority])) {
            return;
        }

        unset($this->sorted[$event]);

        if ($callback === null) {
            unset($this->listeners[$event][$priority]);
            return;
        }

        foreach ($this->listeners[$event][$priority] as $index => $registered) {
            if ($registered === $callback) {
                unset($this->listeners[$event][$priority][$index]);
            }
        }
    }

    /**
     * Rebuilds the priority stack only once per execution.
     */
    protected function getSortedListeners(string $event): array
    {
        if (isset($this->sorted[$event])) {
            return $this->sorted[$event];
        }

        if (!isset($this->listeners[$event])) {
            return $this->sorted[$event] = [];
        }

        ksort($this->listeners[$event]);

        $flattened = [];
        foreach ($this->listeners[$event] as $group) {
            foreach ($group as $listener) {
                $flattened[] = $listener;
            }
        }

        return $this->sorted[$event] = $flattened;
    }

    public function current(): string
    {
        return end($this->stack) ?: '';
    }
}
