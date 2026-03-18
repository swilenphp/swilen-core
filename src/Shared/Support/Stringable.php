<?php

declare(strict_types=1);

namespace Swilen\Shared\Support;

final class Stringable
{
    /**
     * The underlying string value.
     *
     * @var string
     */
    private string $value;

    /**
     * Create a new Stringable instance.
     *
     * @param string $value
     *
     * @return void
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Get current string value.
     *
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Convert the object to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Convert the string to upper-case.
     *
     * @return static
     */
    public function upper(): self
    {
        return new self(mb_strtoupper($this->value));
    }

    /**
     * Convert the string to lower-case.
     *
     * @return static
     */
    public function lower(): self
    {
        return new self(mb_strtolower($this->value));
    }

    /**
     * Trim the string.
     *
     * @param string $characters
     *
     * @return static
     */
    public function trim(string $characters = " \n\r\t\v\0"): self
    {
        return new self(trim($this->value, $characters));
    }

    /**
     * Append value to the string.
     *
     * @param string $value
     *
     * @return static
     */
    public function append(string $value): self
    {
        return new self($this->value . $value);
    }

    /**
     * Prepend value to the string.
     *
     * @param string $value
     *
     * @return static
     */
    public function prepend(string $value): self
    {
        return new self($value . $this->value);
    }

    /**
     * Replace occurrences within the string.
     *
     * @param string|array $search
     * @param string|array $replace
     *
     * @return static
     */
    public function replace(string|array $search, string|array $replace): self
    {
        return new self(str_replace($search, $replace, $this->value));
    }

    /**
     * Limit the number of characters in the string.
     *
     * @param int    $limit
     * @param string $end
     *
     * @return static
     */
    public function limit(int $limit = 100, string $end = '...'): self
    {
        if (mb_strlen($this->value) <= $limit) {
            return new self($this->value);
        }

        return new self(mb_substr($this->value, 0, $limit) . $end);
    }

    /**
     * Transform the string into a URL friendly slug.
     *
     * @param string $divider
     * @param string $default
     *
     * @return static
     */
    public function slug(string $divider = '-', string $default = 'n-a'): self
    {
        return new self(Str::slug($this->value, $divider, $default));
    }

    /**
     * Convert string to camelCase.
     *
     * @return static
     */
    public function camel(): self
    {
        $value = str_replace(['-', '_'], ' ', $this->value);
        $value = lcfirst(str_replace(' ', '', ucwords($value)));

        return new self($value);
    }

    /**
     * Convert string to snake_case.
     *
     * @param string $delimiter
     *
     * @return static
     */
    public function snake(string $delimiter = '_'): self
    {
        $value = preg_replace('/\s+/u', '', ucwords($this->value));
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value);

        return new self(mb_strtolower($value));
    }

    /**
     * Determine if the string contains the given value(s).
     *
     * @param string|string[] $needles
     *
     * @return bool
     */
    public function contains(string|array $needles): bool
    {
        return Str::contains($this->value, $needles);
    }

    /**
     * Determine if the string starts with the given value(s).
     *
     * @param string|string[] $needles
     *
     * @return bool
     */
    public function startsWith(string|array $needles): bool
    {
        return Str::startsWith($this->value, $needles);
    }

    /**
     * Determine if the string ends with the given value(s).
     *
     * @param string|string[] $needles
     *
     * @return bool
     */
    public function endsWith(string|array $needles): bool
    {
        return Str::endsWith($this->value, $needles);
    }

    /**
     * Get the length of the string.
     *
     * @return int
     */
    public function length(): int
    {
        return Str::length($this->value);
    }
}
