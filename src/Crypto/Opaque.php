<?php

namespace Swilen\Crypto;

class Opaque extends Identifier
{
    /**
     * Create a new Opaque ID instance.
     *
     * @param int $bytes Number of random bytes to generate (default: 32)
     *
     * @return static
     */
    public static function new(int $bytes = 32): static
    {
        return new static(\bin2hex(\random_bytes($bytes)));
    }

    protected function validate(string $value): void
    {
        if (\strlen($value) < 32) {
            throw new \InvalidArgumentException('Opaque ID lacks enough entropy.');
        }
    }
}
