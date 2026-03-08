<?php

use Swilen\Pipeline\PriorityPipeline;
use Swilen\Arthropod\Application;
use Swilen\Container\Container;

require_once __DIR__ . '/../../src/Wp/Hooks/functions.php';

uses()->group('Wp', 'Filters');

beforeEach(function () {
    Container::setInstance(null);
    $this->app = new Application(dirname(__DIR__, 2));
    Container::setInstance($this->app);

    $this->pipeline = new PriorityPipeline($this->app);
    $this->app->singleton(PriorityPipeline::class, fn() => $this->pipeline);
});

it('add_filter registers a callback', function () {
    add_filter('test_filter', function ($value) {
        return $value . '_piped';
    });

    expect(has_filter('test_filter'))->toBeTrue();

    $result = apply_filters('test_filter', 'input');

    expect($result)->toBe('input_piped');
});

it('apply_filters chains multiple callbacks in priority order', function () {
    add_filter('priority_filter', function ($value) {
        return $value . '_low';
    }, 100);

    add_filter('priority_filter', function ($value) {
        return $value . '_high';
    }, 1);

    $result = apply_filters('priority_filter', 'start');

    expect($result)->toBe('start_high_low');
});

it('apply_filters passes extra arguments', function () {
    add_filter('args_filter', function ($value, $arg1, $arg2) {
        return $value . '_' . $arg1 . '_' . $arg2;
    });

    $result = apply_filters('args_filter', 'base', 'extra1', 'extra2');

    expect($result)->toBe('base_extra1_extra2');
});
