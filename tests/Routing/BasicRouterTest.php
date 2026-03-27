<?php

use Swilen\Container\Container;
use Swilen\Events\EventDispatcher;
use Swilen\Routing\Router;

uses()->group('Routing');

beforeEach(function () {
    $this->container = new Container();
    $this->events = new EventDispatcher($this->container);
});

afterEach(function () {
    unset($this->container, $this->events);
});

it('basic routing works', function () {
    $router = new Router($this->events, $this->container);

    $router->get('/hello/{name}', function ($name) {
        return "Hello $name";
    });

    $request = fetch('/hello/world');
    $response = $router->dispatch($request);

    expect($response->getBody())->toBe('Hello world');
});

it('routing with parameters works', function () {
    $router = new Router($this->events, $this->container);

    $router->get('/user/{id}/post/{postId}', function ($id, $postId) {
        return ['user_id' => $id, 'post_id' => $postId];
    });

    $request = fetch('/user/123/post/456');
    $response = $router->dispatch($request);

    expect($response->getBody())->toBeJson();
    expect(json_decode($response->getBody(), true))->toEqual(['user_id' => '123', 'post_id' => '456']);
});

it('route groups work', function () {
    $router = new Router($this->events, $this->container);

    $router->prefix('api')->group(function ($router) {
        $router->get('/users', function () {
            return ['users' => []];
        });

        $router->prefix('v1')->group(function ($router) {
            $router->get('/profile', function () {
                return ['version' => 'v1'];
            });
        });
    });

    $request1 = fetch('/api/users');
    $response1 = $router->dispatch($request1);
    expect($response1->getBody())->toBeJson();
    expect(json_decode($response1->getBody(), true))->toEqual(['users' => []]);

    $request2 = fetch('/api/v1/profile');
    $response2 = $router->dispatch($request2);
    expect($response2->getBody())->toBeJson();
    expect(json_decode($response2->getBody(), true))->toEqual(['version' => 'v1']);
});

it('routing handles method not allowed', function () {
    $router = new Router($this->events, $this->container);

    $router->get('/test', function () {
        return 'GET response';
    });

    $request = fetch('/test', 'POST');

    expect(fn () => $router->dispatch($request))->toThrow(\Swilen\Http\Exception\HttpMethodNotAllowedException::class);
});

it('routing handles not found', function () {
    $router = new Router($this->events, $this->container);

    $router->get('/existing', function () {
        return 'found';
    });

    $request = fetch('/nonexistent');

    expect(fn () => $router->dispatch($request))->toThrow(\Swilen\Http\Exception\HttpNotFoundException::class);
});
