<?php

namespace Swilen\Arthropod;

use Swilen\Arthropod\Contract\ExceptionHandler;
use Swilen\Arthropod\Contract\HttpKernel;
use Swilen\Arthropod\Events\AppBootstrappingEvent;
use Swilen\Arthropod\Exception\Handler;
use Swilen\Cache\Cache;
use Swilen\Container\Container;
use Swilen\Events\Dispatcher;
use Swilen\Events\EventDispatcher;
use Swilen\Http\Events\MiddlewareEndEvent;
use Swilen\Http\Events\MiddlewareStartEvent;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Petiole\Facade;
use Swilen\Petiole\ServiceProvider;
use Swilen\Pipeline\Pipeline;
use Swilen\Routing\RoutingServiceProvider;
use Swilen\Shared\Arthropod\Application as ArthropodApplication;

class Application extends Container implements ArthropodApplication, HttpKernel
{
    /**
     * The Swilen current version.
     *
     * @var string
     */
    public const VERSION = '0.0.1-dev';

    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected bool $hasBeenBootstrapped = false;

    /**
     * The application middlewares.
     *
     * @var string[]
     */
    protected array $middlewares = [
        \Swilen\Arthropod\Middleware\CorsMiddleware::class,
        \Swilen\Arthropod\Middleware\LoggerMiddleware::class,
    ];

    /**
     * The boostrabable services collection.
     *
     * @var string[]
     */
    protected array $bootstrappers = [
        \Swilen\Arthropod\Bootstrap\EnvironmentVars::class,
        \Swilen\Arthropod\Bootstrap\Configuration::class,
        \Swilen\Arthropod\Bootstrap\ExceptionsHandler::class,
        \Swilen\Arthropod\Bootstrap\Facades::class, // This should be before register providers to make facades available in providers.
        \Swilen\Arthropod\Bootstrap\RegisterProviders::class,
    ];

    /**
     * The service provider repository.
     *
     * @var \Swilen\Arthropod\ProviderRepository
     */
    protected ProviderRepository $providerRepository;

    /**
     * The application base path.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * The application app path.
     *
     * @var string
     */
    protected string $appPath = 'app';

    /**
     * The application config path.
     *
     * @var string
     */
    protected string $configPath;

    /**
     * The application base uri.
     *
     * @var string
     */
    protected string $appUri;

    /**
     * The application environment path.
     *
     * @var string
     */
    protected string $environmentPath;

    /**
     * The application environment file.
     *
     * @var string
     */
    protected string $environmentFile;

    /**
     * The application events.
     *
     * @var \Swilen\Events\EventDispatcher
     */
    public readonly EventDispatcher $events;

    /**
     * The server factory callback.
     *
     * @var \Closure(): void|null
     */
    protected ?\Closure $serverFactory = null;

    /**
     * Indicates if the server is currently listening.
     *
     * @var bool
     */
    protected bool $isListening = false;

    /**
     * Create http aplication instance.
     *
     * @param string $path define base path for your application
     *
     * @return void
     */
    public function __construct(string $path = '')
    {
        $this->defineBasePath($path);
        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
        $this->registerBaseServiceProviders();

        $this->events = new EventDispatcher($this);
        $this->instance(EventDispatcher::class, $this->events);
        $this->alias(EventDispatcher::class, Dispatcher::class);

        $this->singleton(ExceptionHandler::class, Handler::class);
    }

    /**
     * Start the application server (Swoole).
     * This is the entry point for handling HTTP requests.
     */
    public function listen(): void
    {
        if ($this->serverFactory !== null) {
            if ($this->isListening) {
                throw new \RuntimeException('Server is already listening.');
            }

            ($this->serverFactory)();
            $this->isListening = true;
        }
    }

    /**
     * Return version of Swilen.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Register base container bindings.
     *
     * @return void
     */
    private function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance(ProviderRepository::class, new ProviderRepository($this));
        $this->providerRepository = $this->make(ProviderRepository::class);
    }

    /**
     * Register base service providers.
     *
     * @return void
     */
    private function registerBaseServiceProviders()
    {
        $this->providerRepository->register(new RoutingServiceProvider($this));
    }

    /**
     * Create and define Application base path.
     *
     * @param string $basePath
     *
     * @return $this
     */
    private function defineBasePath($basePath)
    {
        $this->basePath = \rtrim($basePath, '\/');

        $this->registerApplicationPaths();

        return $this;
    }

    /**
     * Register application paths.
     *
     * @return void
     */
    private function registerApplicationPaths()
    {
        $this->instance('path', $this->basePath());
        $this->instance('path.app', $this->appPath());
        $this->instance('path.storage', $this->storagePath());
        $this->instance('path.config', $this->configPath());
    }

    /**
     * Register application path parts.
     *
     * @param string $path
     *
     * @return string
     */
    public function basePath(string $path = '')
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Use application path part.
     *
     * @param string $path
     *
     * @return $this
     */
    public function useBasePath(string $path = '')
    {
        $this->basePath = $path;

        $this->instance('path', $path);

        return $this;
    }

    /**
     * Register application path parts.
     *
     * @param string $path
     *
     * @return string
     */
    public function appPath(string $path = '')
    {
        return $this->basePath($this->appPath) . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

    /**
     * Use application path part.
     *
     * @param string $path
     *
     * @return $this
     */
    public function useAppPath(string $path = '')
    {
        $this->appPath = $path;

        $this->instance('path.app', $path);

        return $this;
    }

    /**
     * Register application path parts.
     *
     * @param string $path
     *
     * @return string
     */
    public function configPath(string $path = '')
    {
        return $this->appPath($path ?: 'app.config.php');
    }

    /**
     * Use application path part.
     *
     * @param string $path
     *
     * @return $this
     */
    public function useConfigPath(string $path = '')
    {
        $this->configPath = $path;

        $this->instance('path.config', $path);

        return $this;
    }

    /**
     * Return application base uri.
     *
     * @param string $path
     *
     * @return string
     */
    public function appUri(string $path = '')
    {
        return $this->appUri . ($path ? '/' . $path : '');
    }

    /**
     * Replace application uri provided from param.
     *
     * @param string $uri
     *
     * @return $this
     */
    public function useAppUri(string $path = '')
    {
        $this->appUri = $path;

        return $this;
    }

    /**
     * Return application base uri.
     *
     * @param string $path
     *
     * @return string
     */
    public function storagePath(string $path = '')
    {
        return $this->appPath('storage') . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Retrive environment file path.
     *
     * @return string
     */
    public function environmentPath()
    {
        return $this->environmentPath ?? $this->basePath();
    }

    /**
     * Use user defined environment file path.
     *
     * @param string $path
     *
     * @return $this
     */
    public function useEnvironmentPath(string $path)
    {
        $this->environmentPath = $path;

        return $this;
    }

    /**
     * Retrive environment filename.
     *
     * @return string
     */
    public function environmentFile()
    {
        return $this->environmentFile ?? '.env';
    }

    /**
     * Use user defined environment filename.
     *
     * @param string $filename
     *
     * @return $this
     */
    public function useEnvironmentFile(string $filename)
    {
        $this->environmentFile = $filename;

        return $this;
    }

    /**
     * Set application environment.
     *
     * @param string $env The Environment valid `production|development|test`
     *
     * @return $this
     */
    public function useEnvironment(string $env)
    {
        $this->instance('env', $env);

        return $this;
    }

    /**
     * Use custom server factory callback. only (Swoole) server runtime is supported for now.
     *
     * @param \Closure(): void $factory
     *
     * @return $this
     */
    public function useServerFactory(\Closure $factory)
    {
        $this->serverFactory = $factory;

        return $this;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerProviders(): void
    {
        $this->providerRepository->push($this->make('config')->get('providers', []));

        $this->providerRepository->load();
    }

    /**
     * Initial register service providers.
     *
     * @param \Swilen\Petiole\ServiceProvider|string $provider
     *
     * @return \Swilen\Petiole\ServiceProvider|null
     */
    public function register($provider): ?ServiceProvider
    {
        return $this->providerRepository->register($provider);
    }

    /**
     * Boot the application with packages that implement the bootstrap contract.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        if ($this->hasBeenBootstrapped()) {
            return;
        }

        $this->events->dispatch(new AppBootstrappingEvent());
        foreach ($this->bootstrappers as $bootstrap) {
            $this->make($bootstrap)->bootstrap($this);
        }

        $this->hasBeenBootstrapped = true;
        // $this->events->dispatch(new AppBootingEvent());
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped(): bool
    {
        return $this->hasBeenBootstrapped === true;
    }

    /**
     * Boot application with boot method into service containers.
     *
     * Its is defer method, it will be called when the first request is handled.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->providerRepository->boot();
    }

    /**
     * Verify if the application is booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->providerRepository->isBooted();
    }

    /**
     * Indicates the application is development mode.
     *
     * @return bool
     */
    public function isDevelopmentMode(): bool
    {
        return (bool) $this->has('env') && $this->make('env') === 'development';
    }

    /**
     * Indicates the application is debug mode.
     *
     * @return bool
     */
    public function isDebugMode(): bool
    {
        return (bool) $this->has('config') && $this->make('config')->get('app.debug', true);
    }

    /**
     * Handle the incoming request and send it to the router.
     *
     * @param \Swilen\Http\Request $request
     *
     * @return \Swilen\Http\Response
     */
    public function handle(Request $request): Response
    {
        try {
            $this->providerRepository->boot();

            $response = $this->dispatchRequestThroughRouter($request);
        } catch (\Throwable $e) {
            $this->reportException($e);

            $response = $this->renderException($e);
        }

        return $response;
    }

    /**
     * Handle for dispatch request for route.
     *
     * @param \Swilen\Http\Request $request
     *
     * @return \Swilen\Http\Response
     */
    protected function dispatchRequestThroughRouter(Request $request)
    {
        $this->instance('request', $request);

        Facade::flushFacadeInstance('request');

        $this->events->dispatch(new MiddlewareStartEvent($request));

        $response = (new Pipeline($this))
            ->from($request)
            ->through($this->middlewares)
            ->then(function ($request) {
                return $this['router']->dispatch($request);
            });

        $this->events->dispatch(new MiddlewareEndEvent($request, $response));

        return $response;
    }

    /**
     * Render given Exception to response.
     *
     * @param \Throwable $e
     *
     * @return \Swilen\Http\Response
     */
    public function renderException(\Throwable $e)
    {
        return $this->make(ExceptionHandler::class)->render($e);
    }

    /**
     * Report given Exception and write log.
     *
     * @param \Throwable $e
     *
     * @return void
     */
    public function reportException(\Throwable $e)
    {
        $this->make(ExceptionHandler::class)->report($e);
    }

    /**
     * Register core aliases into container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
        foreach (
            [
                'app' => \Swilen\Arthropod\Application::class,
                'request' => \Swilen\Http\Request::class,
                'response' => \Swilen\Http\Response::class,
                'router' => \Swilen\Routing\Router::class,
                'cache' => \Swilen\Cache\Cache::class,
                'events' => \Swilen\Events\EventDispatcher::class,
            ] as $key => $value
        ) {
            $this->alias($key, $value);
        }
    }

    /**
     * Flush the application of all bindings and resolved instances.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->providerRepository->flush();

        parent::flush();
    }
}
