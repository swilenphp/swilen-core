<?php

namespace Swilen\Crypto;

class NanoId extends Identifier
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    /**
     * Create a new NanoId instance.
     *
     * @param int $size Length of the NanoId (default: 21)
     *
     * @return static
     */
    public static function new(int $size = 21): static
    {
        $alphabet = self::ALPHABET;

        $bytes = \random_bytes($size);
        $res = '';

        for ($i = 0; $i < $size; $i++) {
            $res .= $alphabet[ord($bytes[$i]) & 63];
        }

        return new static($res);
    }

    protected function validate(string $value): void
    {
        if (strlen($value) < 10 || !preg_match('/^[0-9a-zA-Z_-]+$/', $value)) {
            throw new \InvalidArgumentException('Invalid NanoId format or too short.');
        }
    }
}
