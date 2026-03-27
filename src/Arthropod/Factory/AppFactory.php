<?php

namespace Swilen\Arthropod\Factory;

use Swilen\Arthropod\Application;
use Swilen\Arthropod\Contract\RuntimeContract;
use Swilen\Arthropod\Runtime\Swoole;
use Swilen\Config\Repository;

class AppFactory
{
    /**
     * Create a new factory instance.
     */
    public static function create(string $base_path, ?Repository $config = null, ?RuntimeContract $runtime = null): Application
    {
        $app = new Application($base_path);

        $port = 8080;
        $host = '0.0.0.0';
        $hasConfig = $config !== null;
        if ($config !== null) {
            $app->instance('config', $config);

            $port = (int) $config->get('server.port', $port);
            $host = (string) $config->get('server.host', $host);
        }


        $runtime ??= new Swoole(
            host: $host,
            port: $port,
        );

        $runtime->setContainer($app);

        $app->bootstrap();

        $app->useServerFactory(function () use ($runtime, $app, $hasConfig, $port, $host) {
            $defaultSettings = [
                'port' => $port,
                'host' => $host,
            ];

            if (!$hasConfig) {
                $runtime->updateSettings($app->bound('config') ? $app->make('config')->get('server', $defaultSettings) : $defaultSettings);
            }

            return $runtime->run($app);
        });

        return $app;
    }
}
