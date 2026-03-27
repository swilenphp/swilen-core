<?php

namespace Swilen\Crypto;

use Swilen\Shared\Support\Str;

class Uuid extends Identifier
{
    /**
     * Create a new UUID instance. (Version 4)
     *
     * @return static
     */
    public static function new(): static
    {
        return new static(Str::uuid());
    }

    protected function validate(string $value): void
    {
        if (!Str::isUuid($value)) {
            throw new \InvalidArgumentException("The value '{$value}' is not a valid UUID.");
        }
    }
}
