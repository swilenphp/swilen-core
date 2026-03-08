<?php

namespace Swilen\Cache;

class Node
{
    public string $key;

    /**
     * @var mixed
     */
    public mixed $value;

    public int $expiration;

    public ?Node $prev = null;
    public ?Node $next = null;

    public function __construct(string $key, mixed $value, int $expiration)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiration = $expiration;
    }

    public function expired(): bool
    {
        return $this->expiration > 0 && $this->expiration < time();
    }
}
