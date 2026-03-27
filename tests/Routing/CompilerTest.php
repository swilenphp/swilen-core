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
    @unlink(sys_get_temp_dir() . '/test_routes.php');
    unset($this->container, $this->router);
});

// it('can compile routes to cache file', function () {
//     $this->router->get('/test/{id}', function ($id) {
//         return $id;
//     });

//     $result = $this->router->compileRoutes();

//     expect($result)->toBeTrue();
//     expect(file_exists(sys_get_temp_dir() . '/test_routes.php'))->toBeTrue();
// });

// it('can load compiled routes from cache file', function () {
//     $router1 = new Router($this->container, null, sys_get_temp_dir() . '/test_routes2.php');
//     $router1->get('/test/{id}', function ($id) {
//         return $id . '-compiled';
//     });

//     $router1->compileRoutes();

//     $router2 = new Router($this->container, null, sys_get_temp_dir() . '/test_routes2.php');
//     $router2->loadCompiledRoutes(sys_get_temp_dir() . '/test_routes2.php');

//     /** @var Response */
//     $response = $router2->dispatch(fetch('/test/123'));

//     expect($response->getBody())->toBe('123-compiled');
// });

// it('uses compiled routes if available', function () {
//     $router1 = new Router($this->container, null, sys_get_temp_dir() . '/test_routes3.php');
//     $router1->get('/test/{id}', function ($id) {
//         return $id . '-first';
//     });
//     $router1->compileRoutes();

//     $router2 = new Router($this->container, null, sys_get_temp_dir() . '/test_routes3.php');
//     $router2->loadCompiledRoutes(sys_get_temp_dir() . '/test_routes3.php');

//     // Add a new route to the second router - it should not affect the compiled routes
//     $router2->get('/new', function () {
//         return 'new-route';
//     });

//     /** @var Response */
//     $response = $router2->dispatch(fetch('/test/456'));

//     expect($response->getBody())->toBe('456-first');
// });

// it('fallback to regular routing if compiled routes loading fails', function () {
//     $router = new Router($this->container, null, sys_get_temp_dir() . '/nonexistent.php');
//     $router->get('/test/{id}', function ($id) {
//         return $id . '-fallback';
//     });

//     /** @var Response */
//     $response = $router->dispatch(fetch('/test/789'));

//     expect($response->getBody())->toBe('789-fallback');
// });
