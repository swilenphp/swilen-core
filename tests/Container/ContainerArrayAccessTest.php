<?php

use Swilen\Container\Container;

uses()->group('Container');

beforeEach(function () {
    $this->container = new Container();
});

afterEach(function () {
    unset($this->constainer);
});

it('Array Access set binding', function () {
    $this->container['access'] = function ($app) {
        return true;
    };

    $this->container['simple'] = false;

    expectt(isset($this->container['access']))->toBeTrue();
    expectt(isset($this->container['simple']))->toBeTrue();
});

it('Array Access resolve binding', function () {
    $this->container['access'] = function ($app) {
        return true;
    };

    $this->container['simple'] = false;

    expectt($this->container['access'])->toBeTrue();
    expectt($this->container['simple'])->toBeFalse();
});

it('Array Access remove binding', function () {
    $this->container['access'] = function ($app) {
        return true;
    };

    $this->container['simple'] = false;

    expectt(isset($this->container['access'], $this->container['simple']))->toBeTrue();

    unset($this->container['access'], $this->container['simple']);

    expectt(isset($this->container['access'], $this->container['simple']))->toBeFalse();
});

it('Resolve the target when inserted into the container it is treated as an array with \ArrayAcces', function () {
    $this->container[MongoRepositoryStub::class] = function ($app) {
        return new UserRepositoryStub(100);
    };

    $this->container['depend'] = function ($app) {
        return new class() {
            public function __invoke(MongoRepositoryStub $repository)
            {
                return $repository->find();
            }
        };
    };

    expectt($this->container->call($this->container['depend']))->toBeInt();
});
