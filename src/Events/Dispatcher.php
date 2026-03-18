<?php

namespace Swilen\Events;

interface Dispatcher
{
    /**
     * Dispatch event.
     *
     * @param object|string $event
     * @param array        $payload
     */
    public function dispatch(object|string $event, array $payload = []): void;

    /**
     * Listen to event.
     *
     * @param string $event
     * @param mixed $listener
     * @param int $priority
     */
    public function listen(string $event, mixed $listener, int $priority = 0): void;

    /**
     * Subscribe to event.
     *
     * @param string $event
     * @param mixed $listener
     * @param int $priority
     */
    public function subscribe(string $event, mixed $listener, int $priority = 0): void;

    /**
     * Check if event has listener.
     *
     * @param string $event
     * @return bool
     */
    public function has(string $event): bool;

    /**
     * Get count of fired events.
     *
     * @param string $event
     * @return int
     */
    public function firedCount(string $event): int;

    /**
     * Forget event.
     *
     * @param string $event
     * @param callable|null $handler
     * @param int $priority
     */
    public function forget(string $event, ?callable $handler = null, int $priority = 0): void;

    /**
     * Get current event.
     *
     * @return string
     */
    public function current(): string;
}
