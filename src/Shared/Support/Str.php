<?php

declare(strict_types=1);

namespace Swilen\Shared\Support;

final class Str
{
    /**
     * Determine given neddles itset in given haystack.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     *
     * @return bool
     */
    public static function contains(string $haystack, string|array $needles): bool
    {
        return static::compare(
            $haystack,
            $needles,
            fn ($h, $n) =>
            $n !== '' && str_contains($h, $n)
        );
    }

    /**
     * Determine given haystack start withn given needles.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     *
     * @return bool
     */
    public static function startsWith(string $haystack, string|array $needles): bool
    {
        return static::compare(
            $haystack,
            $needles,
            fn ($h, $n) =>
            str_starts_with($h, $n)
        );
    }

    /**
     * Determine given haystack start withn given needles.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     *
     * @return bool
     */
    public static function endsWith(string $haystack, string|array $needles): bool
    {
        return static::compare(
            $haystack,
            $needles,
            fn ($h, $n) =>
            str_ends_with($h, $n)
        );
    }

    /**
     * High order comparer with given callback.
     *
     * @param string          $haystack
     * @param string|string[] $needles
     * @param \Closure        $callback
     *
     * @return bool
     */
    protected static function compare(string $haystack, string|array $needles, \Closure $callback): bool
    {
        foreach ((array) $needles as $needle) {
            if ($callback($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }

    /**
     * Convert the given string to upper-case.
     *
     * @param string $value
     *
     * @return string
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    /**
     * Get the string matching the given pattern.
     *
     * @param string $pattern
     * @param string $subject
     *
     * @return string
     */
    public static function match(string $pattern, string $subject): string
    {
        if (!preg_match($pattern, $subject, $matches)) {
            return '';
        }

        return $matches[1] ?? $matches[0];
    }

    /**
     * Return the length of the given string.
     *
     * @param string $value
     *
     * @return int
     */
    public static function length(string $value): int
    {
        return mb_strlen($value);
    }

    /**
     * Transform and sanitize the given string.
     *
     * @param string $title
     * @param string $divider
     * @param string $default
     *
     * @return string
     */
    public static function slug(string $title, string $divider = '-', string $default = 'n-a'): string
    {
        // transliterate
        $title = iconv('utf-8', 'us-ascii//TRANSLIT', $title);

        // Convert all dashes/underscores into separator
        $flip = $divider === '-' ? '_' : '-';
        $title = preg_replace('![' . preg_quote($flip) . ']+!u', $divider, $title);

        // Replace @ with the word 'at'
        $title = str_replace('@', $divider . 'at' . $divider, $title);

        // Remove all characters that are not the divider$divider, letters, numbers, or whitespace.
        $title = preg_replace('![^' . preg_quote($divider) . '\pL\pN\s]+!u', '', static::lower($title));

        // Replace all divider$divider characters and whitespace by a single divider$divider
        $title = preg_replace('![' . preg_quote($divider) . '\s]+!u', $divider, $title);

        return trim($title, $divider) ?: $default;
    }

    /**
     * Generate a random UUID (version 4).
     *
     * @return string
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);

        // version 4
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        // variant RFC 4122
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Check given string is a valid uuid format.
     *
     * @param string $uuid
     * @param bool   $v4   Indicates if given uuid is v4
     *
     * @return bool
     */
    public static function isUuid(mixed $uuid, bool $v4 = true): bool
    {
        if (!is_string($uuid)) {
            return false;
        }

        return (bool) preg_match(
            $v4
                ? '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i'
                : '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    /**
     * Remove slashes at the beginning and end of the path..
     *
     * @param string $path
     *
     * @return string
     */
    public static function trimPath(mixed $path = ''): string
    {
        return !is_string($path) ? '' : '/' . trim($path ?: '/', '\/');
    }

    /**
     * Get a new Stringable instance.
     *
     * @param string $value
     *
     * @return \Swilen\Shared\Support\Stringable
     */
    public static function of(string $value): Stringable
    {
        return new Stringable($value);
    }
}
