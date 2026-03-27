<?php

namespace Swilen\Crypto;

use JsonSerializable;
use Stringable;

abstract class Identifier implements Stringable, JsonSerializable
{
    /**
     * El valor es inmutable y solo lectura.
     */
    protected readonly string $value;

    protected function __construct(string $value)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Reconstruye un ID desde un string (ej. desde una Request o DB).
     */
    public static function from(string $value): static
    {
        return new static($value);
    }

    /**
     * Generación segura delegada a las subclases.
     */
    abstract public static function new(): static;

    /**
     * Validación estricta de formato.
     */
    abstract protected function validate(string $value): void;

    /**
     * Secure comparison to prevent timing attacks.
     */
    public function equals(mixed $other): bool
    {
        $compare = $other instanceof static ? $other->value : (string) $other;

        if (\function_exists('hash_equals')) {
            return \hash_equals($this->value, $compare);
        }

        $knownLen = \strlen($this->value);
        $userLen  = \strlen($compare);

        $result = $knownLen ^ $userLen;

        $minLen = $knownLen < $userLen ? $knownLen : $userLen;

        for ($i = 0; $i < $minLen; $i++) {
            $result |= \ord($this->value[$i]) ^ \ord($compare[$i]);
        }

        return $result === 0;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
