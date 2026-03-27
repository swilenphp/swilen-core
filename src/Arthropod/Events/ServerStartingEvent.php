<?php

namespace Swilen\Arthropod\Events;

final class ServerStartingEvent
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly array $settings
    ) {
    }
}
