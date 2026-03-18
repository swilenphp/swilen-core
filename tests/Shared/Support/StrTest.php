<?php

use Swilen\Shared\Support\Str;
use Swilen\Validation\Regex;

uses()->group('Support', 'Arr');

it('If string contains given value', function () {
    expectt(Str::contains('cuzco', 'z'))->toBeTrue();
    expectt(Str::contains('cuzco', '20'))->toBeFalse();
});

it('String ends with', function () {
    expectt(Str::endsWith('foo>todo', 'todo'))->toBeTrue();
    expectt(Str::endsWith('foo>todo', 'falsy'))->toBeFalse();
    expectt(Str::endsWith('', 'search'))->toBeFalse();
});

it('String starts with', function () {
    expectt(Str::startsWith('foo>todo', 'foo'))->toBeTrue();
    expectt(Str::startsWith('foo>todo', 'falsy'))->toBeFalse();
});

it('String get length', function () {
    expectt(Str::length(''))->toBe(0);
    expectt(Str::length('hello'))->toBe(5);
    expectt(Str::length("hello\35"))->toBe(6);
});

it('String transform to upper', function () {
    expectt(Str::upper(''))->toBe('');
    expectt(Str::upper('hello'))->toBe('HELLO');
    expectt(Str::upper('hello_'))->toBe('HELLO_');
});

it('String transform to lower', function () {
    expectt(Str::lower(''))->toBe('');
    expectt(Str::lower('HEllo'))->toBe('hello');
    expectt(Str::lower('OtHer'))->toBe('other');
});

it('String match', function () {
    expectt(Str::match('/[0-9]+/', 'Helo35'))->toBe('35');
    expectt(Str::match('/[0-9]/', 'Helo'))->toBe('');
});

it('String slug transform', function () {
    expectt(Str::slug(' Must be a Title'))->toBe('must-be-a-title');
    expectt(Str::slug(' Must be a Title', '_'))->toBe('must_be_a_title');
    expectt(Str::slug('', '_', '__default'))->toBe('__default');
});

it('String generate uuid', function () {
    expectt(Str::uuid())->toMatch(Regex::UUID_V4);
});

it('String check if given value is uuid', function () {
    expectt(Str::isUuid([]))->toBeFalse();
    expectt(Str::isUuid(Str::uuid()))->toBeTrue();
});
