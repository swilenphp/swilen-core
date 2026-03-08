<?php

use Swilen\Events\EventDispatcher;
use Swilen\Arthropod\Application;
use Swilen\Container\Container;

require_once __DIR__ . '/../../src/Wp/Plugins/functions.php';
require_once __DIR__ . '/../../src/Wp/Hooks/functions.php';

uses()->group('Wp', 'Actions');

beforeEach(function () {
    $this->dispatcher = new EventDispatcher();

    Container::setInstance(null);
    Container::getInstance()->singleton(EventDispatcher::class, fn() => $this->dispatcher);
    Container::getInstance()->singleton(\Swilen\Events\Dispatcher::class, fn() => $this->dispatcher);
});

afterEach(function () {
    Container::setInstance(null);
});

it('add_action registers a callback for an event', function () {
    $called = false;

    add_action('test_hook', function () use (&$called) {
        $called = true;
    });

    expect(has_action('test_hook'))->toBeTrue();

    do_action('test_hook');

    expect($called)->toBeTrue();
});

it('do_action executes all registered callbacks', function () {
    $results = [];

    add_action('multi_callback', function () use (&$results) {
        $results[] = 'first';
    });

    add_action('multi_callback', function () use (&$results) {
        $results[] = 'second';
    });

    do_action('multi_callback');

    expect($results)->toBe(['first', 'second']);
});

it('do_action passes arguments to callbacks', function () {
    $received = [];

    add_action('with_args', function ($arg1, $arg2) use (&$received) {
        $received = [$arg1, $arg2];
    });

    do_action('with_args', 'value1', 'value2');

    expect($received)->toBe(['value1', 'value2']);
});

it('remove_action removes specific callback', function () {
    $called1 = false;
    $called2 = false;

    $callback1 = function () use (&$called1) {
        $called1 = true;
    };
    $callback2 = function () use (&$called2) {
        $called2 = true;
    };

    add_action('remove_test', $callback1, 10);
    add_action('remove_test', $callback2, 10);

    do_action('remove_test');
    expect($called1)->toBeTrue();
    expect($called2)->toBeTrue();

    $called1 = false;
    $called2 = false;

    remove_action('remove_test', $callback1, 10);

    do_action('remove_test');
    expect($called1)->toBeFalse();
    expect($called2)->toBeTrue();
});

it('has_action returns false for unregistered hooks', function () {
    expect(has_action('nonexistent'))->toBeFalse();
});

it('did_action returns correct count', function () {
    expect(did_action('count_test'))->toBe(0);

    do_action('count_test');
    expect(did_action('count_test'))->toBe(1);

    do_action('count_test');
    expect(did_action('count_test'))->toBe(2);

    do_action('other_hook');
    expect(did_action('count_test'))->toBe(2);
});

it('actions work with priority', function () {
    $order = [];

    add_action('priority_test', function () use (&$order) {
        $order[] = 'low';
    }, 100);

    add_action('priority_test', function () use (&$order) {
        $order[] = 'high';
    }, 1);

    add_action('priority_test', function () use (&$order) {
        $order[] = 'default';
    }, 10);

    do_action('priority_test');

    expect($order)->toBe(['high', 'default', 'low']);
});

it('multiple hooks work independently', function () {
    $hookA = false;
    $hookB = false;

    add_action('hook_a', function () use (&$hookA) {
        $hookA = true;
    });

    add_action('hook_b', function () use (&$hookB) {
        $hookB = true;
    });

    do_action('hook_a');

    expect($hookA)->toBeTrue();
    expect($hookB)->toBeFalse();

    do_action('hook_b');

    expect($hookB)->toBeTrue();
});
