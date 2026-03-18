<?php

use Swilen\Container\Container;

uses()->group('Container');

it('Set instance for interact as singleton', function () {
    $container = Container::setInstance(new Container());
    expectt($container)->toBeInstanceOf(Container::class);

    unset($container);
});

it('Get container as singleton instance', function () {
    Container::setInstance();

    $instance = Container::getInstance();
    expectt($instance)->toBeInstanceOf(Container::class);

    expectt($instance->getInstance())->toBeTruthy();
});
