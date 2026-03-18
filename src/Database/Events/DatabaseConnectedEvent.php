<?php

namespace Swilen\Database\Events;

final class DatabaseConnectedEvent
{
    public function __construct(public readonly string $driver) {}
}
