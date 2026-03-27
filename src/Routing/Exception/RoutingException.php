<?php

namespace Swilen\Routing\Exception;

/**
 * Thrown when the routing engine cannot resolve a handler or one of its dependencies.
 */
class RoutingException extends \RuntimeException
{
    /**
     * Create exception for an unresolvable handler.
     *
     * @param string $handler
     * @param string $reason
     *
     * @return static
     */
    public static function forUnresolvableHandler(string $handler, string $reason = ''): static
    {
        $message = "Cannot resolve route handler [{$handler}]";

        if ($reason !== '') {
            $message .= ": {$reason}";
        }

        return new static($message);
    }

    /**
     * Create exception for an unresolvable dependency.
     *
     * @param string $dependency
     * @param string $handler
     *
     * @return static
     */
    public static function forUnresolvableDependency(string $dependency, string $handler): static
    {
        return new static(
            "Cannot resolve dependency [{$dependency}] for handler [{$handler}]."
        );
    }

    /**
     * Create exception when compiled cache file not found.
     *
     * @param string $path
     *
     * @return static
     */
    public static function forMissingCacheFile(string $path): static
    {
        return new static("Compiled route cache file not found at [{$path}].");
    }

    /**
     * Create exception for an invalid cache file.
     *
     * @param string $path
     *
     * @return static
     */
    public static function forInvalidCacheFile(string $path): static
    {
        return new static("Compiled route cache file at [{$path}] is invalid or corrupt.");
    }
}
