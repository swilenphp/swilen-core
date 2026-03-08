<?php

namespace Swilen\Wp\Hooks;

use Swilen\Events\Event;

class WpHookEvent implements Event
{
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
}
