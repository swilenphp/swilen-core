<?php

use Swilen\Routing\RouteAction;

uses()->group('Routing', 'RoutingAction');

it('Resolve uses action is closure', function () {
    $action = RouteAction::parse('/', InvokableTestingStub::class);

    expectt($action['uses'])->toBe(InvokableTestingStub::class . '@__invoke');
    expectt($action)->not->toHaveKey('controller');

    $action = RouteAction::parse('/', function () {
        return 'closure';
    });

    expectt($action['uses'])->toBeCallable();
    expectt($action['uses']())->toBe('closure');
    expectt($action)->not->toHaveKey('controller');
});

it('Resolve uses action is controller', function ($actin) {
    $action = RouteAction::parse('/', $actin);

    expectt($action)->toHaveKeys(['controller', 'uses']);
    expectt($action['uses'])->toBe('MethodAllowedControllerStub@index');
    expectt($action['controller'])->toBe('MethodAllowedControllerStub@index');
})->with([
    'array' => [[MethodAllowedControllerStub::class, 'index']],
    'string' => 'MethodAllowedControllerStub@index',
]);

it('Throw uses action is null or empty', function ($actin) {
    $action = RouteAction::parse('/', $actin);

    expectt($action['uses'])->toBeCallable();
    expectt($action)->not->toHaveKey('controller');
    call_user_func($action['uses']);
})->with([
    'null' => null,
    'empty_array' => [[]],
    'empty' => '',
])->throws(LogicException::class);

it('Throw uses action not contains __invoke method', function () {
    RouteAction::parse('/', MethodAllowedControllerStub::class);
})->throws(UnexpectedValueException::class, 'Invalid route action: [MethodAllowedControllerStub]');

class InvokableTestingStub
{
    public function __invoke()
    {
        return 5;
    }
}

class MethodAllowedControllerStub
{
    public function index()
    {
        return 'Hi!';
    }
}
