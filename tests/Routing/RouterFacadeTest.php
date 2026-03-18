<?php

use Swilen\Container\Container;
use Swilen\Petiole\Facade;
use Swilen\Petiole\Facades\Route;
use Swilen\Routing\Route as RoutingRoute;
use Swilen\Routing\Router;
use Swilen\Security\Middleware\Authenticate;

uses()->group('Routing');

beforeAll(function () {
    $app = Container::getInstance();

    $app->singleton('router', function ($app) {
        return new Router($app);
    });

    Facade::setFacadeApplication($app);
});

afterAll(function () {
    Container::getInstance()->flush();
});

it('Router Facade registered succesfully', function () {
    $route = Route::get('/hola', function () {
        return ['hola' => 'Mundo'];
    })->name('test-hola')->use(Authenticate::class);

    expectt($route)->toBeInstanceOf(RoutingRoute::class);
    expectt($route->getName())->toBe('test-hola');
    expectt($route->middlewares())->toBe([Authenticate::class]);
    expectt($route->getMethod())->toBe('GET');
});
