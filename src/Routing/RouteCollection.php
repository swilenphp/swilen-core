<?php

namespace Swilen\Routing;

use Swilen\Http\Common\Method;
use Swilen\Http\Exception\HttpMethodNotAllowedException;
use Swilen\Http\Exception\HttpNotFoundException;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Shared\Support\Arrayable;

class RouteCollection implements Arrayable, \IteratorAggregate, \Countable
{
    /**
     * The routes collection.
     *
     * @var array<string, \Swilen\Routing\Route[]>
     */
    protected $routes = [];

    /**
     * Count all routes.
     *
     * @var int
     */
    protected $count = 0;

    /**
     * Methods registered in the collection.
     *
     * @var string[]
     */
    protected $methods = [];

    /**
     * The application container instance.
     *
     * @var \Swilen\Container\Container;
     */
    protected $container;

    /**
     * The router instance used by the routes collection.
     *
     * @var \Swilen\Routing\Router
     */
    protected $router;

    /**
     * Add new Route to collection.
     *
     * @param \Swilen\Routing\Route $route
     *
     * @return \Swilen\Routing\Route
     */
    public function add(Route $route)
    {
        $this->addRouteToCollection($route);

        return $route;
    }

    /**
     * Add route to collection adn increment count of routes.
     *
     * @param \Swilen\Routing\Route $route
     *
     * @return void
     */
    protected function addRouteToCollection(Route $route)
    {
        $this->routes[$route->getMethod()][] = $route;

        ++$this->count;
    }

    /**
     * Find and match route from current method and current request action.
     *
     * @param \Swilen\Http\Request $request
     *
     * @return \Swilen\Routing\Route
     *
     * @throws \Swilen\Http\Exception\HttpMethodNotAllowedException
     * @throws \Swilen\Http\Exception\HttpNotFoundException
     */
    public function match(Request $request)
    {
        $routes = $this->get($request->getMethod());

        // We try if we can find a matching route for this current request
        $route = $this->matchRoutes($routes, $request);

        return $this->handleMatchedRoute($request, $route);
    }

    /**
     * @param string $method
     *
     * @return \Swilen\Routing\Route[]
     */
    protected function get(string $method)
    {
        return $this->routes[$method] ?? [];
    }

    /**
     * Match route in all routes match by method.
     *
     * @param \Swilen\Routing\Route[] $routes
     * @param \Swilen\Http\Request    $request
     *
     * @return \Swilen\Routing\Route|null
     */
    protected function matchRoutes(array $routes, Request $request)
    {
        foreach ($routes as $route) {
            if ($route->matches($request->getPathInfo())) {
                return $route;
            }
        }
    }

    /**
     * Handle the matched route.
     *
     * @param \Swilen\Http\Request       $request
     * @param \Swilen\Routing\Route|null $route
     *
     * @return \Swilen\Routing\Route
     */
    protected function handleMatchedRoute(Request $request, $route)
    {
        if ($route !== null) {
            return $route;
        }

        if (!empty($methods = $this->findAllowedMethods($request))) {
            return $this->handleRouteForMethods($request, $methods);
        }

        throw new HttpNotFoundException();
    }

    /**
     * Find allowed methods for this URI by matching against all other HTTP methods as well.
     *
     * @param \Swilen\Http\Request $request
     *
     * @return string[]
     */
    protected function findAllowedMethods(Request $request)
    {
        $methods = array_diff(Router::HTTP_METHODS, [$request->getMethod()]);

        return array_filter($methods, function ($method) use ($request) {
            return $this->matchRoutes($this->get($method), $request) !== null;
        });
    }

    /**
     * Manage request method is another in route collection.
     *
     * @param \Swilen\Http\Request $request
     * @param string[]             $methods
     *
     * @return \Swilen\Routing\Route|null
     *
     * @throws \Swilen\Http\Exception\HttpMethodNotAllowedException
     */
    protected function handleRouteForMethods(Request $request, array $methods = [])
    {
        if ($request->getMethod() === Method::OPTIONS->value) {
            return new Route(Method::OPTIONS->value, $request->getPathInfo(), function () use ($methods) {
                return new Response('', 200, ['Allow' => implode(', ', $methods)]);
            });
        }

        $this->methodNotAllowed($request->getMethod(), $methods);
    }

    /**
     * Handle exepcion if method not allowed in route collection.
     *
     * @param string   $method
     * @param string[] $methods
     *
     * @throws \Swilen\Http\Exception\HttpMethodNotAllowedException
     */
    protected function methodNotAllowed(string $method, array $methods = [])
    {
        throw HttpMethodNotAllowedException::forMethod($method, $methods);
    }

    /**
     * Compile all routes in collection to be ready for matching.
     *
     * @return array<{static: \Swilen\Routing\Route[], blocks: string[], map: array<int, array{method: string, handler: \Closure|string, vars: string[], middleware: array}>}>
     */
    public function compile(): array
    {
        $static = [];
        $dynamic = [];
        $metadata = [];

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $route) {
                $pattern = $route->getPattern();

                if (strpos($pattern, '{') === false) {
                    $static[$method][$pattern] = [
                        'handler' => $route->getAction('uses'),
                        'middleware' => $route->getMiddleware()
                    ];
                    continue;
                }

                $dynamic[] = $route;
            }
        }

        usort($dynamic, function ($a, $b) {
            return strlen($b->getPattern()) <=> strlen($a->getPattern());
        });

        $chunks = array_chunk($dynamic, 100);
        $blocks = [];

        foreach ($chunks as $chunkIndex => $chunk) {
            $patterns = [];
            foreach ($chunk as $localIndex => $route) {
                $globalId = ($chunkIndex * 100) + $localIndex;

                $compiled = RouteMatching::compile($route->getPattern());

                $patterns[] = $compiled['regex'] . "(*MARK:$globalId)";

                $metadata[$globalId] = [
                    'method' => $route->getMethod(),
                    'handler' => $route->getAction('uses'),
                    'vars' => $compiled['vars'],
                    'middleware' => $route->getMiddleware()
                ];
            }
            $blocks[] = '~^(?|' . implode('|', $patterns) . ')$~x';
        }
        return [
            'static' => $static,
            'blocks' => $blocks,
            'map'    => $metadata
        ];
    }

    /**
     * Get all the routes in the collection.
     *
     * @return array<string, \Swilen\Routing\Route[]>
     */
    public function routes()
    {
        return $this->routes;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $routes = [];

        foreach ($this->routes as $method => $route) {
            $routes[$method] = array_map(function ($e) {
                return $e->toArray();
            }, $route);
        }

        return $routes;
    }

    /**
     * Get count all routes.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return $this->count;
    }

    /**
     * Get iterator for all routes.
     *
     * @return \ArrayIterator<string,\Swilen\Routing\Route[]>
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->routes);
    }
}
