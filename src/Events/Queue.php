<?php

namespace Swilen\Events;

interface Queue
{
    public function push(callable $job, int $priority = 0): void;

    public function run(): void;
}
