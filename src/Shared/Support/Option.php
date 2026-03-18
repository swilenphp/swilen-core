<?php

namespace Swilen\Shared\Support;

/**
 * @template T
 */
class Option
{
    /**
     * @var T|null
     */
    private mixed $value;

    private static Option $empty;

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
     * @return Option<U>
     */
    public function map(callable $fn): Option
    {
        if ($this->value === null) {
            return new Option(null);
        }

        return new Option($fn($this->value));
    }

    /**
     * @template U
     * @param callable(T):Option<U> $fn
     * @return Option<U>
     */
    public function flatMap(callable $fn): Option
    {
        if ($this->value === null) {
            return new Option(null);
        }

        return $fn($this->value);
    }

    /**
     * @param callable(T):bool $fn
     * @return Option<T>
     */
    public function filter(callable $fn): Option
    {
        if ($this->value === null) {
            return $this;
        }

        return $fn($this->value) ? $this : new Option(null);
    }

    public function isPresent(): bool
    {
        return $this->value !== null;
    }

    public function ifPresent(callable $fn): void
    {
        if ($this->value !== null) {
            $fn($this->value);
        }
    }

    public function isEmpty(): bool
    {
        return $this->value === null;
    }

    /**
     * @template T
     * @param T $value
     * @return Option<T>
     */
    public static function of(mixed $value): Option
    {
        return new Option($value);
    }

    /**
     * @return Option<null>
     */
    public static function empty(): Option
    {
        if (isset(self::$empty)) {
            return self::$empty;
        }

        return self::$empty = new Option(null);
    }
}
