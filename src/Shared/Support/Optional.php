<?php

namespace Swilen\Shared\Support;

/**
 * @template T
 */
class Optional
{
    /**
     * @var T|null
     */
    private mixed $value;

    /**
     * @param T|null $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * @return T|null
     */
    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @param T $default
     * @return T
     */
    public function orElse(mixed $default): mixed
    {
        return $this->value ?? $default;
    }

    /**
     * @template U
     * @param callable(T):U $fn
     * @return Optional<U>
     */
    public function map(callable $fn): Optional
    {
        if ($this->value === null) {
            return new Optional(null);
        }

        return new Optional($fn($this->value));
    }

    /**
     * @template U
     * @param callable(T):Optional<U> $fn
     * @return Optional<U>
     */
    public function flatMap(callable $fn): Optional
    {
        if ($this->value === null) {
            return new Optional(null);
        }

        return $fn($this->value);
    }

    /**
     * @param callable(T):bool $fn
     * @return Optional<T>
     */
    public function filter(callable $fn): Optional
    {
        if ($this->value === null) {
            return $this;
        }

        return $fn($this->value) ? $this : new Optional(null);
    }

    public function isPresent(): bool
    {
        return $this->value !== null;
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }

    /**
     * @template T
     * @param T $value
     * @return Optional<T>
     */
    public static function of(mixed $value): Optional
    {
        return new Optional($value);
    }

    /**
     * @return Optional<null>
     */
    public static function empty(): Optional
    {
        return new Optional(null);
    }
}
