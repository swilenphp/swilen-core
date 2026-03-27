<?php

namespace Swilen\Routing\Contract;

use Swilen\Http\Request;

interface RouterAdapterContract
{
    /**
     * Add a route to the collection.
     *
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @param mixed $handler Route handler
     *
     * @return mixed Route reference
     */
    public function addRoute(string $method, string $pattern, $handler): mixed;

    /**
     * Get dispatcher for the router.
     *
     * @return mixed
     */
    public function getDispatcher(): mixed;

    /**
     * Dispatch request to matching route.
     *
     * @param string $method
     * @param string $uri
     *
     * @return mixed Dispatch result
     */
    public function dispatch(string $method, string $uri): mixed;

    /**
     * Add a group of routes.
     *
     * @param string $prefix Group prefix
     * @param array $attributes Group attributes
     * @param callable $callback Group callback
     *
     * @return void
     */
    public function addGroup(string $prefix, array $attributes, callable $callback): void;

    /**
     * Get all registered routes.
     *
     * @return array
     */
    public function getRoutes(): array;
}
