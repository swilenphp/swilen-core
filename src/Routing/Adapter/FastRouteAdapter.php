<?php

namespace Swilen\Routing\Adapter;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StdParser;
use Swilen\Routing\Contract\RouterAdapterContract;

class FastRouteAdapter implements RouterAdapterContract
{
    /**
     * The FastRoute route collector.
     *
     * @var RouteCollector
     */
    protected $collector;

    /**
     * The cached dispatcher instance.
     *
     * @var GroupCountBasedDispatcher|null
     */
    protected $dispatcher;

    /**
     * The route parser.
     *
     * @var StdParser
     */
    protected $parser;

    /**
     * The data generator.
     *
     * @var GroupCountBasedGenerator
     */
    protected $generator;

    /**
     * Compiled routes file path.
     *
     * @var string|null
     */
    protected $compiledRoutesPath;

    /**
     * Route definitions for caching (minimal data to reconstruct routes).
     *
     * @var array
     */
    protected $routeDefinitions = [];

    /**
     * Create new FastRoute adapter instance.
     *
     * @param string|null $compiledRoutesPath Path to store compiled routes
     */
    public function __construct(?string $compiledRoutesPath = null)
    {
        $this->parser = new StdParser();
        $this->generator = new GroupCountBasedGenerator();
        $this->collector = new RouteCollector($this->parser, $this->generator);
        $this->compiledRoutesPath = $compiledRoutesPath;
    }

    /**
     * {@inheritdoc}
     */
    public function addRoute(string $method, string $pattern, $handler): mixed
    {
        $this->collector->addRoute($method, $pattern, $handler);

        // Store minimal route definition for compilation
        $this->routeDefinitions[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];

        $this->dispatcher = null;

        return $handler;
    }

    /**
     * {@inheritdoc}
     */
    public function getDispatcher(): mixed
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = new GroupCountBasedDispatcher(
                $this->collector->getData()
            );
        }

        return $this->dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(string $method, string $uri): mixed
    {
        $this->loadCompiledRoutesIfAvailable();

        return $this->getDispatcher()->dispatch($method, $uri);
    }

    /**
     * {@inheritdoc}
     */
    public function addGroup(string $prefix, array $attributes, callable $callback): void
    {
        $this->collector->addGroup($prefix, function (RouteCollector $collector) use ($callback) {
            $callback($collector);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return $this->collector->getData()['GET'] ?? [];
    }

    /**
     * Load compiled routes from cache if available.
     *
     * @return void
     */
    protected function loadCompiledRoutesIfAvailable(): void
    {
        if ($this->compiledRoutesPath !== null && file_exists($this->compiledRoutesPath)) {
            $data = require $this->compiledRoutesPath;

            if (is_array($data)) {
                $this->rebuildCollectorFromData($data);
            }
        }
    }

    /**
     * Save compiled routes to cache file.
     *
     * @return bool
     */
    public function compileRoutes(): bool
    {
        if ($this->compiledRoutesPath === null) {
            return false;
        }

        // Rebuild collector with minimal data to avoid circular references
        $this->rebuildCollectorWithDefinitions();

        $data = $this->collector->getData();
        $code = '<?php return ' . var_export($data, true) . ';';

        return file_put_contents($this->compiledRoutesPath, $code) !== false;
    }

    /**
     * Rebuild the collector with just the route definitions (avoiding circular refs).
     *
     * @return void
     */
    protected function rebuildCollectorWithDefinitions(): void
    {
        // Create fresh parser and generator
        $this->parser = new StdParser();
        $this->generator = new GroupCountBasedGenerator();
        $this->collector = new RouteCollector($this->parser, $this->generator);

        // Add all stored route definitions
        foreach ($this->routeDefinitions as $definition) {
            $this->collector->addRoute(
                $definition['method'],
                $definition['pattern'],
                $definition['handler']
            );
        }

        $this->dispatcher = null;
    }

    /**
     * Rebuild the collector from cached data.
     *
     * @param array $data
     *
     * @return void
     */
    protected function rebuildCollectorFromData(array $data): void
    {
        // Create fresh parser and generator
        $this->parser = new StdParser();
        $this->generator = new GroupCountBasedGenerator();
        $this->collector = new RouteCollector($this->parser, $this->generator);

        // Replace the collector's data with our cached data
        $reflection = new \ReflectionObject($this->collector);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $property->setValue($this->collector, $data);

        $this->dispatcher = null;
    }

    /**
     * Get the underlying FastRoute collector.
     *
     * @return RouteCollector
     */
    public function getCollector(): RouteCollector
    {
        return $this->collector;
    }

    /**
     * Get the route definitions for compilation.
     *
     * @return array
     */
    public function getRouteDefinitions(): array
    {
        return $this->routeDefinitions;
    }

    /**
     * Set the compiled routes path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setCompiledRoutesPath(string $path): self
    {
        $this->compiledRoutesPath = $path;

        return $this;
    }
}
