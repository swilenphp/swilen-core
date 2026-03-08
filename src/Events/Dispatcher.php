<?php

namespace Swilen\Events;

interface Dispatcher
{
    public function dispatch(Event|string $event, array $payload = []): void;

    public function listen(string $event, callable|Listener $listener, int $priority = 0): void;

    public function has(string $event): bool;

    public function firedCount(string $event): int;

    public function forget(string $event, ?callable $handler = null, int $priority = 0): void;

    public function current(): string;
}
