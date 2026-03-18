<?php

use Swilen\Container\Helper;

uses()->group('Container');

it('Get parameters from Reflection class', function () {
    $class = new ReflectionClass(GetParameterClassNameStub::class);

    $parameters = $class->getConstructor()->getParameters();

    expectt(Helper::getParameterClassName($parameters[0]))->toBe('Closure');
    expectt(Helper::getParameterClassName($parameters[1]))->toBeNull();

    $declaringParameters = new ReflectionParameter([ExtendingParameterNamedStub::class, 'method'], 0);

    expectt(Helper::getParameterClassName($declaringParameters))->toBe(ReflectionObject::class);
});

class GetParameterClassNameStub
{
    public function __construct(Closure $callback, int $age) {}

    public function method(ReflectionObject $callback) {}
}

class ExtendingParameterNamedStub extends GetParameterClassNameStub {}
