<?php

namespace Swilen\Routing\Adapter;

use Swilen\Http\Request;

class CompiledRouteMatcher
{
    /**
     * The compiled routes data.
     *
     * @var array
     */
    protected $routes;

    /**
     * Create new compiled route matcher instance.
     *
     * @param array $routes Compiled routes from FastRoute
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Match request against compiled routes.
     *
     * @param Request $request
     *
     * @return array{0: mixed, 1: array} [handler, vars]
     */
    public function match(Request $request): array
    {
        $method = $request->getMethod();
        $uri = $this->normalizeUri($request->getPathInfo());

        if (!isset($this->routes[$method])) {
            return [null, []];
        }

        foreach ($this->routes[$method] as $route) {
            if ($this->matchRoute($route, $uri, $vars)) {
                return [$route['handler'], $vars];
            }
        }

        return [null, []];
    }

    /**
     * Match a single route against URI.
     *
     * @param array $route
     * @param string $uri
     * @param array &$vars
     *
     * @return bool
     */
    protected function matchRoute(array $route, string $uri, array &$vars = []): bool
    {
        $regex = $route['regex'] ?? null;

        if ($regex === null) {
            return $route['route'] === $uri;
        }

        if (preg_match('#^' . $regex . '$#', $uri, $matches)) {
            $vars = $this->extractVars($route, $matches);
            return true;
        }

        return false;
    }

    /**
     * Extract variables from regex matches.
     *
     * @param array $route
     * @param array $matches
     *
     * @return array
     */
    protected function extractVars(array $route, array $matches): array
    {
        $vars = [];
        $varNames = $route['variables'] ?? [];

        foreach ($varNames as $index => $name) {
            if (isset($matches[$index + 1])) {
                $vars[$name] = $matches[$index + 1];
            }
        }

        return $vars;
    }

    /**
     * Normalize URI for matching.
     *
     * @param string $uri
     *
     * @return string
     */
    protected function normalizeUri(string $uri): string
    {
        return '/' . trim($uri, '/');
    }
}
