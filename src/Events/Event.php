<?php

namespace Swilen\Events;

interface Event
{
    public const WP_PREFIX = '__wp__';

    public function name(): string;

    public function payload(): array;
}
