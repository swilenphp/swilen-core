<?php

use Swilen\Container\Container;
use Swilen\Container\Method;

uses()->group('Container');

beforeEach(function () {
    $this->container = new Container();
});

afterEach(function () {
    unset($this->container);
});

it('Call method', function () {
    $result = Method::call($this->container, function () {
        return 10;
    });

    expectt($result)->toBeInt();
});

it('Call invoke class', function () {
    $result = Method::call($this->container, InvokableStub::class);

    expectt($result)->toBeInt();
});

it('Call invoke class in array', function () {
    $result = Method::call($this->container, InvokableStub::class . '@__invoke');

    expectt($result)->toBeInt();
});

it('Call class method as normal', function () {
    $result = Method::call($this->container, InvokableStub::class . '@method');
    expectt($result)->toBeString();

    $result = Method::call($this->container, [new InvokableStub(), 'method']);
    expectt($result)->toBeString();
});

it('Call method binding into container', function () {
    $this->container->bindMethod(InvokableStub::class . '@method', function ($app) {
        return 'echo';
    });

    $result = Method::call($this->container, InvokableStub::class . '@method');

    expectt($result)->toBe('echo');
});

it('Call class method as static', function () {
    $result = Method::call($this->container, InvokableStub::class . '::staticMethod');

    expectt($result)->toBeArray();
});

it('Throw error when call class without method and not contains __invoke method', function () {
    Method::call($this->container, NormalyClassStub::class . '@');
})->throws(InvalidArgumentException::class, 'Method not provided.');

it('Call Closure with dependency injection in parameter', function () {
    $this->container->bind(MongoRepositoryStub::class, function ($app) {
        return new UserRepositoryStub(100);
    });

    $closure = function (MongoRepositoryStub $repository) {
        return $repository->find();
    };

    expectt($this->container->call($closure))->toBeInt();
});

it('Call __invoke function was class is used as function', function () {
    $this->container->bind(MongoRepositoryStub::class, function ($app) {
        return new UserRepositoryStub(100);
    });

    $class = new class () {
        public function __invoke(MongoRepositoryStub $repository)
        {
            return $repository->find();
        }
    };

    $result = $this->container->call($class);

    expectt($result)->toBeInt();
});

it('call Closure with parameters', function () {
    $result = Method::call($this->container, function ($var) {
        return $var;
    }, ['var' => 'foo']);
    expectt($result)->toBe('foo');

    $result = Method::call($this->container, function ($val = 'bar') {
        return $val;
    });
    expectt($result)->toBe('bar');

    $instance       = new stdClass();
    $instance->data = true;

    $result = Method::call($this->container, NormalyClassStub::class . '@withParams', [
        stdClass::class => $instance,
    ]);
    expectt($result)->toBeInstanceOf(stdClass::class);
    expectt($result->data)->toBeTrue();
});

it('call Closure with variadic parameters', function () {
    $result = Method::call($this->container, function (...$var) {
        return $var;
    }, ['foo', 'bar']);
    expectt($result)->toBe(['foo', 'bar']);

    $result = Method::call($this->container, NormalyVariadicClassStub::class . '@method');
    expectt($result)->toBe(100);
});

it('Throw error when call Closure missing parameters', function () {
    Method::call($this->container, function ($var) {
        return $var;
    });
})->throws(RuntimeException::class);

class InvokableStub
{
    public function __invoke()
    {
        return 10;
    }

    public function method()
    {
        return 'string';
    }

    public static function staticMethod()
    {
        return [];
    }
}

class NormalyClassStub
{
    public function method()
    {
        return 100;
    }

    public function withParams(stdClass $object)
    {
        return $object;
    }
}

class NormalyVariadicClassStub
{
    public function method(NormalyClassStub ...$args)
    {
        return array_reduce($args, function ($stack, $arg) {
            return $stack += $arg->method();
        }, 0);
    }
}
