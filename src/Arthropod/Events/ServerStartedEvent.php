<?php

namespace Swilen\Arthropod\Events;

final class ServerStartedEvent
{
    public function __construct(
        public readonly string $host,
        public readonly int $port
    ) {
    }
}
