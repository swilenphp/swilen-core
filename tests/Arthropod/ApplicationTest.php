<?php

use Swilen\Arthropod\Application;
use Swilen\Arthropod\Env;
use Swilen\Container\Container;
use Swilen\Petiole\Facade;
use Swilen\Shared\Arthropod\Application as ArthropodApplication;

uses()->group('Application');

beforeAll(function () {
    $app = new Application(dirname(__DIR__));

    $app->useEnvironmentPath(dirname(__DIR__));

    $app->singleton(
        \Swilen\Arthropod\Contract\ExceptionHandler::class,
        \Swilen\Arthropod\Exception\Handler::class
    );
});

beforeEach(function () {
    /**
     * @var \PHPUnit\Framework\TestCase $this
     */
    $app = Application::getInstance();

    /**
     * @var mixed $this
     */
    $this->app = $app;
});

afterAll(function () {
    Env::forget();
    Application::getInstance()->flush();
    Facade::flushFacadeInstances();
});

it('The application started successfully and your instance is correct', function () {
    /**
     * @var Swilen\Arthropod\Application
     */
    $app = $this->app;

    expectt($app)->toBeInstanceOf(Application::class);
    expectt($app)->toBeInstanceOf(ArthropodApplication::class);
    expectt($app)->toBeInstanceOf(Container::class);
    expectt($app)->toBeObject();
    expectt($app->hasBeenBootstrapped())->toBeFalse();
    expectt($app->isBooted())->toBeFalse();
});

it('Interact with application paths and files', function () {
    $directory = dirname(__DIR__);

    $app = new Application($directory);

    expectt($app->environmentFile())->toBe('.env');

    $app->useEnvironmentFile('.env.example');
    expectt($app->environmentFile())->toBe('.env.example');

    expectt($app->environmentPath())->toBe($directory);
    $app->useEnvironmentPath('/test');
    expectt($app->environmentPath())->toBe('/test');
});

it('Application is developent mode', function () {
    $app = new Application(dirname(__DIR__));

    expectt($app->isDevelopmentMode())->toBeFalse();

    $app->useEnvironment('development');

    expectt($app->isDevelopmentMode())->toBeTrue();
});

it('Flush application instance', function () {
    $app = new Application(dirname(__DIR__));

    expectt(count($app->bindings()))->toBeGreaterThanOrEqual(2);

    $app->flush();

    expectt($app->bindings())->toBeEmpty();

    unset($app);
});

final class HelperTesting
{
    public function retrieve()
    {
        return 10;
    }
}
