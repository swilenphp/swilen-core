<?php

namespace Swilen\Routing\Result;

/**
 * Immutable value-object returned by FastRouteDispatcher.
 *
 * The dispatcher always returns one of three states:
 *
 *   NOT_FOUND        – no route pattern matched the URI at all
 *   METHOD_NOT_ALLOWED – a route matched the URI but not the HTTP method;
 *                        $allowedMethods lists what IS allowed
 *   FOUND            – a route matched; $handler, $parameters, $middleware
 *                      and $name are populated
 *
 * Only native scalars and arrays are stored so the object stays lightweight
 * on the hot dispatch path.
 */
final class RouteResult
{
    public const NOT_FOUND            = 0;
    public const FOUND                = 1;
    public const METHOD_NOT_ALLOWED   = 2;

    /**
     * @param int                        $status         One of the class constants above.
     * @param \Closure|string|array|null $handler        Resolved action (FOUND only).
     * @param array<string, string>      $parameters     Extracted URI parameters (FOUND only).
     * @param array                      $middleware     Middleware stack for the matched route.
     * @param string|null                $name           Route name, if any.
     * @param string[]                   $allowedMethods HTTP methods allowed (METHOD_NOT_ALLOWED only).
     */
    public function __construct(
        public readonly int $status,
        public readonly \Closure|string|array|null $handler    = null,
        public readonly array $parameters                      = [],
        public readonly array $middleware                      = [],
        public readonly ?string $name                          = null,
        public readonly ?string $pattern                       = null,
        public readonly array $allowedMethods                  = [],
    ) {
    }

    // -------------------------------------------------------------------------
    // Named constructors — zero overhead in the dispatch hot-path
    // -------------------------------------------------------------------------

    public static function found(
        \Closure|string|array $handler,
        array $parameters = [],
        array $middleware  = [],
        ?string $name      = null,
        ?string $pattern   = null,
    ): self {
        return new self(
            status:     self::FOUND,
            handler:    $handler,
            parameters: $parameters,
            middleware: $middleware,
            name:       $name,
            pattern:    $pattern,
        );
    }

    public static function notFound(): self
    {
        return new self(self::NOT_FOUND);
    }

    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self(
            status:         self::METHOD_NOT_ALLOWED,
            allowedMethods: $allowedMethods,
        );
    }

    // -------------------------------------------------------------------------
    // Convenience predicates
    // -------------------------------------------------------------------------

    public function isFound(): bool
    {
        return $this->status === self::FOUND;
    }

    public function isNotFound(): bool
    {
        return $this->status === self::NOT_FOUND;
    }

    public function isMethodNotAllowed(): bool
    {
        return $this->status === self::METHOD_NOT_ALLOWED;
    }
}
