<?php

use Swilen\Container\Container;
use Swilen\Container\Exception\BindingResolutionException;

uses()->group('Container');

beforeEach(function () {
    $this->container = new Container();
});

afterEach(function () {
    unset($this->container);
});

it('Add contextual binding for concrete type', function () {
    $this->container->addContextualBinding(
        ContextualConsumerStub::class,
        ContextualDependencyInterface::class,
        ContextualDependencyB::class
    );

    $consumer = $this->container->make(ContextualConsumerStub::class);

    expectt($consumer->dependency)->toBeInstanceOf(ContextualDependencyB::class);
});

it('Contextual binding overrides default binding', function () {
    $this->container->bind(ContextualDependencyInterface::class, ContextualDependencyA::class);

    $this->container->addContextualBinding(
        ContextualConsumerStub::class,
        ContextualDependencyInterface::class,
        ContextualDependencyB::class
    );

    $consumer = $this->container->make(ContextualConsumerStub::class);

    expectt($consumer->dependency)->toBeInstanceOf(ContextualDependencyB::class);
});

it('Use default binding when no contextual binding exists', function () {
    $this->container->bind(ContextualDependencyInterface::class, ContextualDependencyA::class);

    $consumer = $this->container->make(ContextualConsumerStub::class);

    expectt($consumer->dependency)->toBeInstanceOf(ContextualDependencyA::class);
});

it('Call method with dependencies injected', function () {
    $service = new MethodInjectionStub();

    $result = $this->container->callMethod($service, 'process', ['name' => 'test']);

    expectt($result)->toBe('processed: test');
});

it('Call method with default parameters', function () {
    $service = new MethodInjectionStub();

    $result = $this->container->callMethod($service, 'process');

    expectt($result)->toBe('processed: default');
});

it('Call closure with dependencies injected', function () {
    $closure = function (MethodInjectionDependency $dep, string $name = 'closure') {
        return $dep->value . ': ' . $name;
    };

    $this->container->bind(MethodInjectionDependency::class, fn () => new MethodInjectionDependency('injected'));

    $result = $this->container->call($closure);

    expectt($result)->toBe('injected: closure');
});

it('Call function with dependencies injected', function () {
    $this->container->instance('test-value', 'from-container');

    $result = $this->container->call(function (MethodInjectionDependency $dep, $testValue = 'default') {
        return $dep->value . ' - ' . $testValue;
    });

    expectt($result)->toBe('dependency-value - default');
});

it('Resolve deeply nested dependencies', function () {
    $instance = $this->container->make(NestedLevel1::class);

    expectt($instance)->toBeInstanceOf(NestedLevel1::class);
    expectt($instance->level2)->toBeInstanceOf(NestedLevel2::class);
    expectt($instance->level2->level3)->toBeInstanceOf(NestedLevel3::class);
});

it('Resolve dependencies with interface bindings', function () {
    $this->container->bind(CircularAInterface::class, CircularA::class);
    $this->container->bind(CircularBInterface::class, CircularB::class);

    $this->container->make(CircularAInterface::class);
})->throws(BindingResolutionException::class, 'Circular dependency detected');

it('Resolve callback bound via bindMethod', function () {
    $service = new MethodBindingService();

    $this->container->bindMethod([MethodBindingService::class, 'handle'], function ($service) {
        return $service->value . '-custom';
    });

    $result = $this->container->callMethodBinding(MethodBindingService::class . '@handle', $service);

    expectt($result)->toBe('default-custom');
});

it('Return original method when no binding exists', function () {
    $service = new MethodBindingService();

    $result = $this->container->callMethodBinding(MethodBindingService::class . '@handle', $service);

    expectt($result)->toBe('default');
});

it('Extend singleton after resolution', function () {
    $this->container->singleton(ExtensibleService::class);

    $instance1 = $this->container->make(ExtensibleService::class);
    $instance1->value = 'original';

    $this->container->extend(ExtensibleService::class, function ($service, $app) {
        $service->value = 'extended';
        return $service;
    });

    $instance2 = $this->container->make(ExtensibleService::class);

    expectt($instance2->value)->toBe('extended');
});

it('Extend non-singleton applies extenders to new instance', function () {
    $this->container->bind(ExtensibleService::class);

    $this->container->extend(ExtensibleService::class, function ($service, $app) {
        $service->extended = true;
        return $service;
    });

    $instance = $this->container->make(ExtensibleService::class);

    expectt($instance)->toHaveProperty('extended');
    expectt($instance->extended)->toBeTrue();
});

it('Null key throws exception on offsetSet', function () {
    $this->container[null] = 'value';
})->throws(\Exception::class, 'Unable to set key is null');

it('Retrieve binding with array access', function () {
    $this->container->bind('test', fn () => 'bound-value');

    expectt($this->container['test'])->toBe('bound-value');
});

it('Unset removes binding', function () {
    $this->container['test'] = 'value';

    expectt(isset($this->container['test']))->toBeTrue();

    unset($this->container['test']);

    expectt(isset($this->container['test']))->toBeFalse();
});

it('Chained alias resolution', function () {
    $this->container->alias(OriginalClass::class, 'alias1');
    $this->container->alias('alias1', 'alias2');

    $this->container->bind(OriginalClass::class, fn () => new OriginalClass());

    expectt($this->container->make('alias2'))->toBeInstanceOf(OriginalClass::class);
});

it('Forget instance removes resolved instance but keeps binding', function () {
    $this->container->bind('service', fn () => new stdClass());

    $instance1 = $this->container->make('service');

    $this->container->forgetInstance('service');

    expectt($this->container->resolved('service'))->toBeFalse();
    expectt($this->container->has('service'))->toBeTrue();
});

it('Resolve optional class dependency', function () {
    $instance = $this->container->make(OptionalDependencyClass::class);

    expectt($instance)->toBeInstanceOf(OptionalDependencyClass::class);
    expectt($instance->optional)->toBeNull();
});

it('Resolve variadic parameter creates instances', function () {
    $instance = $this->container->make(VariadicOptional::class);

    expectt($instance->items)->toBeArray();
    expectt(count($instance->items))->toBe(1);
});

it('Bind with class name as abstract and concrete', function () {
    $this->container->bind(ConcreteOnlyStub::class);

    expectt($this->container->has(ConcreteOnlyStub::class))->toBeTrue();

    $instance = $this->container->make(ConcreteOnlyStub::class);
    expectt($instance)->toBeInstanceOf(ConcreteOnlyStub::class);
});

it('Unbind removes binding completely', function () {
    $this->container->bind('to-unbind', fn () => 'value');

    expectt($this->container->has('to-unbind'))->toBeTrue();

    $this->container->unbind('to-unbind');

    expectt($this->container->has('to-unbind'))->toBeFalse();
});

it('IsShared returns true for singleton', function () {
    $this->container->singleton('singleton-service', fn () => new stdClass());

    expectt($this->container->isShared('singleton-service'))->toBeTrue();
});

it('IsShared returns false for regular binding', function () {
    $this->container->bind('regular-service', fn () => new stdClass());

    expectt($this->container->isShared('regular-service'))->toBeFalse();
});

it('Resolved returns true after making instance', function () {
    $this->container->bind('test-resolved', fn () => 'value');

    expectt($this->container->resolved('test-resolved'))->toBeFalse();

    $this->container->make('test-resolved');

    expectt($this->container->resolved('test-resolved'))->toBeTrue();
});

it('Instance is already resolved', function () {
    $this->container->instance('pre-resolved', new stdClass());

    expectt($this->container->resolved('pre-resolved'))->toBeTrue();
});

it('Tag multiple items with same tag', function () {
    $this->container->tag([TagStubA::class, TagStubB::class], 'multi-tag');

    expectt($this->container->isTag('multi-tag'))->toBeTrue();

    $tagged = $this->container->tagged('multi-tag');
    $count = 0;
    foreach ($tagged as $item) {
        $count++;
    }

    expectt($count)->toBe(2);
});

it('IsTag returns false for non-existent tag', function () {
    expectt($this->container->isTag('non-existent'))->toBeFalse();
});

interface ContextualDependencyInterface
{
    public function getValue(): string;
}

class ContextualDependencyA implements ContextualDependencyInterface
{
    public function getValue(): string
    {
        return 'A';
    }
}

class ContextualDependencyB implements ContextualDependencyInterface
{
    public function getValue(): string
    {
        return 'B';
    }
}

class ContextualConsumerStub
{
    public $dependency;

    public function __construct(ContextualDependencyInterface $dep)
    {
        $this->dependency = $dep;
    }
}

class MethodInjectionDependency
{
    public string $value;

    public function __construct(string $value = 'dependency-value')
    {
        $this->value = $value;
    }
}

class MethodInjectionStub
{
    public function process(MethodInjectionDependency $dep, string $name = 'default'): string
    {
        return 'processed: ' . $name;
    }
}

class NestedLevel3
{
    public string $value = 'level3';
}

class NestedLevel2
{
    public NestedLevel3 $level3;

    public function __construct(NestedLevel3 $level3)
    {
        $this->level3 = $level3;
    }
}

class NestedLevel1
{
    public NestedLevel2 $level2;

    public function __construct(NestedLevel2 $level2)
    {
        $this->level2 = $level2;
    }
}

interface CircularAInterface
{
}

interface CircularBInterface
{
}

class CircularA
{
    public CircularBInterface $b;

    public function __construct(CircularBInterface $b)
    {
        $this->b = $b;
    }
}

class CircularB
{
    public CircularAInterface $a;

    public function __construct(CircularAInterface $a)
    {
        $this->a = $a;
    }
}

class CircularConsumer
{
    public CircularAInterface $a;
    public CircularBInterface $b;

    public function __construct(CircularAInterface $a, CircularBInterface $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}

class MethodBindingService
{
    public string $value = 'default';

    public function handle(): string
    {
        return $this->value;
    }
}

class ExtensibleService
{
    public string $value = '';
    public bool $extended = false;
}

class OriginalClass
{
}

class OptionalDependencyClass
{
    public ?OptionalDependencyClass $optional;

    public function __construct(?OptionalDependencyClass $optional = null)
    {
        $this->optional = $optional;
    }
}

class VariadicOptional
{
    public array $items;

    public function __construct(MethodInjectionDependency ...$items)
    {
        $this->items = $items;
    }
}

class ConcreteOnlyStub
{
}

class TagStubA
{
    public function report(): string
    {
        return 'A';
    }
}

class TagStubB
{
    public function report(): string
    {
        return 'B';
    }
}
