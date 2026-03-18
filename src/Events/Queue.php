<?php

namespace Swilen\Events;

interface Queue
{
    /**
     * Push job to queue.
     *
     * @param callable $job
     * @param int $priority
     */
    public function push(callable $job, int $priority = 0): void;

    /**
     * Run queue.
     */
    public function run(): void;
}
