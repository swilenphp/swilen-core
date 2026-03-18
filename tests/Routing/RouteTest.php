<?php

use Swilen\Http\Common\Method;
use Swilen\Http\Response;
use Swilen\Routing\Exception\HttpResponseException;
use Swilen\Routing\Exception\InvalidRouteHandlerException;
use Swilen\Routing\Route;

uses()->group('Routing');

it('Route instanced succesfuly', function () {
    $route = new Route(Method::GET->value, '/hola', function () {
        return 5;
    });

    expectt($route->getMethod())->toBe(Method::GET->value);
    expectt($route->getPattern())->toBe('/hola');
    expectt($route->getAction('uses'))->toBeCallable();
    expectt($route->run())->toBe(5);
    expectt($route->getParameters())->toBeEmpty();
});

it('Throw error when action is invalid: classname not exists', function ($action) {
    $route = new Route(Method::GET->value, 'test', $action);

    $route->run();
})->with([
    'controller_string' => 'controller@invoke',
    'controller_array' => [['controller\\class', 'invoke']],
])->throws(InvalidRouteHandlerException::class);

it('Run route action is controller', function ($action) {
    $route = new Route(Method::GET->value, 'test', $action);

    expectt($route->run())->toBe(5);
})->with([
    'controller_string' => ControllerTestStub::class . '@method',
    'controller_array' => [[ControllerTestStub::class, 'method']],
]);

it('Run route action is callable or invocable', function ($action) {
    $route = new Route(Method::GET->value, 'test', $action);

    expectt($route->run())->toBe(5);
})->with([
    'closure' => function () {
        return function () {
            return 5;
        };
    },
    'invocable' => InvocableTestStub::class,
]);

it('Compile parameters matched', function () {
    $route = new Route('GET', 'test/{home}', function ($home) {
        return $home;
    });

    expectt($route->matches('test/lima'))->toBeTrue();
    expectt($route->matches('test/25'))->toBeTrue();

    $route->matches('test/cuzco');

    expectt($route->run())->toBe('cuzco');
});

it('Compile parameters matched with data-type', function () {
    $route = new Route('GET', 'test/{string:home}', function ($home) {
        return $home;
    });

    expectt($route->matches('test/lima'))->toBeTrue();
    expectt($route->matches('test/25'))->toBeTrue();

    $route->matches('test/machu-picchu');

    expectt($route->run())->toBe('machu-picchu');

    $route = new Route('GET', 'person/{int:age}', function (int $age) {
        return $age;
    });

    expectt($route->matches('person/lima'))->toBeFalse();
    expectt($route->matches('person/25'))->toBeTrue();

    $route->matches('person/25');

    expectt($route->run())->toBeInt();
});

it('Correct resolve url encoded', function () {
    $route = new Route('GET', 'test/{url}', function ($url) {
        return $url;
    });

    expectt($route->matches('test/Cuzco%2C%20Peru'))->toBeTrue();

    expectt($route->run())->toBe('Cuzco, Peru');
});

it('Resolve multiples parameter names', function () {
    $route = new Route('GET', 'named/{uri}/{other}/{domain}', function () {
        return null;
    });

    expectt($route->matches('named/my-uri/blog/google'))->toBeTrue();

    expectt($route->run())->toBeNull();
    expectt($route->parameterNames())->toBe(['uri', 'other', 'domain']);
    expectt($route->parameterNames())->toBe(['uri', 'other', 'domain']);
});

it('Run route catch if error is ResponseException', function () {
    $route = new Route('GET', '/name', function () {
        throw new HttpResponseException(new Response('Function called error', 401));
    });

    expectt($route->run())->toBeInstanceOf(Response::class);
});

class InvocableTestStub
{
    public function __invoke()
    {
        return 5;
    }
}

class ControllerTestStub
{
    public function method()
    {
        return 5;
    }
}
