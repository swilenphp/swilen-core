<?php

namespace Swilen\Arthropod;

use Swilen\Arthropod\Events\AppBootedEvent;
use Swilen\Arthropod\Events\AppBootingEvent;
use Swilen\Petiole\ServiceProvider;

class ProviderRepository
{
    /**
     * The application instance.
     *
     * @var \Swilen\Arthropod\Application
     */
    protected Application $app;

    /**
     * The service provider class names that have been registered.
     *
     * @var string[]
     */
    protected array $store = [];

    /**
     * Resolved service provider collection.
     *
     * @var \Swilen\Petiole\ServiceProvider[]
     */
    protected array $providers = [];

    /**
     * Collection of service providers as registered.
     *
     * @var array<string, bool>
     */
    protected array $providersRegistered = [];

    /**
     * Indicates if providers have been booted.
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * @param \Swilen\Arthropod\Application $app
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Push a service provider to the repository.
     *
     * @param array $providers
     *
     * @return void
     */
    public function push(array $providers = []): void
    {
        foreach ($providers as $provider) {
            $this->add($provider);
        }
    }

    /**
     * Add a service provider to the repository.
     *
     * @param string $provider
     *
     * @return void
     */
    public function add($provider): void
    {
        $this->store[] = $provider;
    }

    /**
     * Get all service providers in the repository.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->store;
    }

    public function load(): void
    {
        foreach ($this->store as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Register a service provider.
     *
     * @param \Swilen\Petiole\ServiceProvider|string $provider
     *
     * @return \Swilen\Petiole\ServiceProvider|null
     */
    public function register($provider): ?ServiceProvider
    {
        if ($this->hasBeenRegistered($provider)) {
            return null;
        }

        $provider = $this->createProvider($provider);

        $provider->register();

        $this->markAsRegistered($provider);

        return $provider;
    }

    /**
     * Create a new provider instance.
     *
     * @param \Swilen\Petiole\ServiceProvider|string $provider
     *
     * @return \Swilen\Petiole\ServiceProvider
     */
    protected function createProvider($provider): ServiceProvider
    {
        return \is_string($provider) ? new $provider($this->app) : $provider;
    }

    /**
     * Mark service provider as registered.
     *
     * @param \Swilen\Petiole\ServiceProvider $provider
     *
     * @return void
     */
    protected function markAsRegistered(ServiceProvider $provider): void
    {
        $this->providers[] = $provider;

        $this->providersRegistered[\get_class($provider)] = true;
    }

    /**
     * Determine if service provider has been registered.
     *
     * @param \Swilen\Petiole\ServiceProvider|string $provider
     *
     * @return bool
     */
    public function hasBeenRegistered($provider): bool
    {
        $provider = \is_object($provider) ? \get_class($provider) : $provider;

        return isset($this->providersRegistered[$provider])
            && $this->providersRegistered[$provider] === true;
    }

    /**
     * Boot all registered service providers.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->app->events->dispatch(new AppBootingEvent());

        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $this->app->call([$provider, 'boot']);
            }
        }

        $this->booted = true;
        $this->app->events->dispatch(new AppBootedEvent());
    }

    /**
     * Check if providers have been booted.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Flush the repository of all providers.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->providers = [];
        $this->providersRegistered = [];
        $this->booted = false;
    }
}
