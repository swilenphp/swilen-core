<?php

use Swilen\Container\Container;
use Swilen\Events\EventDispatcher;
use Swilen\Http\Exception\HttpForbiddenException;
use Swilen\Http\Exception\HttpMethodNotAllowedException;
use Swilen\Http\Exception\HttpNotFoundException;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Routing\Route;
use Swilen\Routing\RouteCollection;
use Swilen\Routing\Router;
use Swilen\Security\Middleware\Authenticate;

uses()->group('Routing');

beforeEach(function () {
    $this->container = new Container();
    $this->events = new EventDispatcher($this->container);
    $this->router = new Router($this->events, $this->container);
});

afterEach(function () {
    unset($this->container, $this->events, $this->router);
});

it('Match route current request', function () {
    $this->router->get('/test', function () {
        return ['slwien' => 'test'];
    });

    /** @var Response */
    $response = $this->router->dispatch(fetch('/test'));

    expectt($response)->toBeInstanceOf(Response::class);
    expectt($response->getBody())->toBeJson();
});

it('Throw not found if route not matches', function () {
    $this->router->get('/test', function () {
        return 'Testing Not Found';
    });

    $this->router->dispatch(fetch('/testing'));
})->throws(HttpNotFoundException::class, 'Not Found.');

it('Throw if current method not implement in routes collection', function () {
    $this->router->get('/testing', function () {
        return 'Test Expect';
    });

    /** @var \Swilen\Http\Response */
    $response = $this->router->dispatch(fetch('/testing', 'POST'));

    expectt($response->headers->all())->toHaveKey('Allow');
})->throws(HttpMethodNotAllowedException::class, 'The POST method is not supported. Must be one of: GET.');

it('Routing register shared middleware and return throw if bearer token not found in header', function () {
    $this->router->prefix('users')->use(Authenticate::class)->group(function () {
        $this->router->get('test', function () {
            return 1;
        })->name('user-test');
    });

    $this->router->dispatch(fetch('/users/test'));
})->throws(HttpForbiddenException::class, 'Forbidden');

it('Routing register shared middleware and return throw if bearer token found', function () {
    $this->router->prefix('users')->use(Authenticate::class)->group(function () {
        $this->router->get('test', function () {
            return 1;
        })->name('user-test');
    });

    $this->router->dispatch(fetch('/users/test', 'GET', [
        'Authorization' => '',
    ]));
})->throws(HttpForbiddenException::class, 'Forbidden');

it('Route attributes as registered', function () {
    /** @var \Swilen\Routing\Route */
    $route = $this->router->get('/hello/{world}', function () {
        return 5;
    })->name('api.hello')->use(function (Request $request, Closure $next) {
        $response = $next($request);

        $response->withHeader('Fo', 'bar');

        return $response;
    });

    expectt($route->getPattern())->toBe('/hello/{world}');
    expectt($route->getName())->toBe('api.hello');
    expectt($route->getMiddleware())->toBeArray();
    expectt($route->getAction('uses'))->toBeCallable();
    expectt($route->getAction())->not->toHaveKey('controller');

    $response = $this->router->dispatch(fetch('/hello/lima'));

    expectt($response->getBody())->toBeNumeric();
    expectt($response->headers->get('Fo'))->toBe('bar');

    expectt($route->parameter('world'))->toBe('lima');
    expectt($route->getParameters())->not->toBeEmpty();
});

it('Shared route attributes registered', function () {
    /* @var \Swilen\Routing\Route */

    $this->router->use(function (Request $request, Closure $next) {
        $response = $next($request);

        $response->withHeader('Use-Token', 'true');

        return $response;
    })->prefix('name')->group(function () {
        $this->router->get('/route/{match}', function () {
            return ['hi!'];
        });
    });

    $response = $this->router->dispatch(fetch('/name/route/cuzco'));

    expectt($response->getBody())->toBeJson();
    expectt($response->headers->get('Use-Token'))->toBe('true');

    /** @var \Swilen\Routing\Route */
    $route = $this->router->current();

    expectt($route->getPattern())->toBe('/name/route/{match}');
    expectt($route->getName())->toBeNull();
    expectt($route->getMiddleware())->toBeArray();
    expectt($route->getAction('uses'))->toBeCallable();
    expectt($route->getAction())->not->toHaveKey('controller');
    expectt($route->parameter('match'))->toBe('cuzco');
});

it('Route created with given http method by router method', function () {
    $router = new Router(new EventDispatcher(), new Container());
    $handler = function () {
        return 5;
    };

    $get = $router->get('/', $handler);

    expectt($get)->toBeInstanceOf(Route::class);
    expectt($get->getMethod())->toBe('GET');

    expectt($router->post('', $handler)->getMethod())->toBe('POST');
    expectt($router->put('', $handler)->getMethod())->toBe('PUT');
    expectt($router->delete('', $handler)->getMethod())->toBe('DELETE');
    expectt($router->patch('', $handler)->getMethod())->toBe('PATCH');
    expectt($router->options('', $handler)->getMethod())->toBe('OPTIONS');
});

it('Get all routes as RouteCollection instance from a router', function () {
    $router = new Router(new EventDispatcher(), new Container());
    $handler = function () {
        return 5;
    };

    expectt($router->routes())->toBeInstanceOf(RouteCollection::class);
});
