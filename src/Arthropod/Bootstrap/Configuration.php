<?php

namespace Swilen\Arthropod\Bootstrap;

use Swilen\Arthropod\Contract\BootstrappableService;
use Swilen\Config\Repository;
use Swilen\Shared\Arthropod\Application;

class Configuration implements BootstrappableService
{
    /**
     * Application instance.
     */
    protected Application $app;

    /**
     * Allowed application environments.
     */
    protected array $modes = ['development', 'production', 'test'];

    /**
     * Bootstrap application config.
     */
    public function bootstrap(Application $app): void
    {
        $this->app = $app;

        $this->loadConfiguration();
    }

    /**
     * Load and register configuration repository.
     */
    protected function loadConfiguration(): void
    {
        // If the application already has a config repository instance, we will not attempt to load configuration file again.
        if (!($this->app->has('config') && ($config = $this->app->make('config')) instanceof Repository)) {
            $configPath = $this->ensureConfigPath(
                $this->app->make('path.config')
            );

            $configData = require $configPath;

            if (!\is_array($configData)) {
                throw new \UnexpectedValueException(
                    \sprintf('Configuration file "%s" must return an array, %s returned.', $configPath, \gettype($configData))
                );
            }

            $this->app->instance('config', $config = new Repository($configData));
        }

        $this->validateEnvironment($config);

        $this->normalizePhpInternalConfig($config);
    }

    /**
     * Validate application environment.
     */
    protected function validateEnvironment(Repository $config): void
    {
        $env = (string) $config->get('app.env', 'production');

        if (!\in_array($env, $this->modes, true)) {
            throw new \LogicException(
                \sprintf('Invalid application environment "%s". Allowed values: [%s].', $env, implode(', ', $this->modes))
            );
        }

        $this->app->useEnvironment($env);
    }

    /**
     * Validate config file path.
     *
     * @throws \InvalidArgumentException
     */
    public function ensureConfigPath(string $config = ''): string
    {
        $config = $config ?: $this->app->appPath('app.config.php');

        if (!\is_string($config) || $config === '') {
            throw new \InvalidArgumentException(
                'Configuration path could not be resolved (empty or invalid value).'
            );
        }

        if (!\file_exists($config)) {
            throw new \InvalidArgumentException(
                \sprintf('Configuration file not found at path: "%s".', $config)
            );
        }

        if (!\is_readable($config)) {
            throw new \InvalidArgumentException(
                \sprintf('Configuration file "%s" is not readable. Check file permissions.', $config)
            );
        }

        return $config;
    }

    /**
     * Configure internal PHP settings.
     */
    protected function normalizePhpInternalConfig(Repository $config): void
    {
        $timezone = (string) $config->get('app.timezone', 'UTC');

        if (!@\date_default_timezone_set($timezone)) {
            throw new \RuntimeException(
                \sprintf('Invalid timezone "%s" defined in configuration.', $timezone)
            );
        }

        if (!\function_exists('mb_internal_encoding')) {
            throw new \RuntimeException(
                'mbstring extension is required but not enabled.'
            );
        }

        \mb_internal_encoding('UTF-8');
    }
}
