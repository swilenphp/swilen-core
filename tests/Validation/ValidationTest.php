<?php

use Swilen\Validation\Exception\RuleNotFoundException;
use Swilen\Validation\Rule;
use Swilen\Validation\Validator;

uses()->group('Validation');

it('Validator init with __constructor or make method', function () {
    $validator     = new Validator([]);
    $makeValidator = Validator::make([]);

    expectt($validator)->toBeInstanceOf(Validator::class);
    expectt($makeValidator)->toBeInstanceOf(Validator::class);
});

it('Validator implements Arrayable', function () {
    $validator = Validator::make([
        'email' => 'example.com',
        'name' => '647',
        'age' => 18,
    ]);

    expectt($validator->toArray())->toBeArray();
    expectt($validator->toArray())->toHaveKeys(['inputs', 'errors', 'rules']);
});

it('Validate simple inputs', function () {
    $validator = Validator::make([
        'email' => 'example@domain.com',
        'name' => 'Second',
        'age' => 18,
    ]);

    $validator->validate([
        'email' => 'email|required',
        'name' => 'required|alpha',
        'age' => 'required|int',
    ]);

    expectt($validator->fails())->toBeFalse();
});

it('Validate simple inputs is same time', function () {
    $validator = Validator::make(
        [
            'email' => 'example@domain.com',
        ],
        [
            'email' => 'email|required',
        ]
    );

    expectt($validator->fails())->toBeFalse();
});

it('Parse rules from array or string', function () {
    $validator = Validator::make(['attribute' => 1, 'other' => 'alpha'], [
        'attribute' => ['int', 'number', Rule::NULLABLE],
        'other' => 'alpha|nullable',
    ]);

    expectt($validator->fails())->toBeFalse();
});

it('Parse rule arguments', function () {
    $time = strtotime('2022-02-10');

    $validator = Validator::make(['date' => date('Y-m-d', $time), 'country' => 'Peru'], [
        'date' => 'date:Y-m-d|required',
        'country' => 'in:Peru,Colombia,Ecuador|required',
    ]);

    expectt($validator->fails())->toBeFalse();
});

it('Skip rule if is regex', function () {
    $validator = Validator::make(['country' => 'Peru'], [
        'country' => ['regex:/(Peru|Colombia|Bolivia)/', 'required'],
    ]);

    expectt($validator->fails())->toBeFalse();
});

it('Add message when validate is fails', function () {
    $validator = Validator::make([
        'email' => 'example.com',
        'name' => '647',
        'age' => 18,
    ]);

    $validator->validate([
        'email' => 'email|required',
        'name' => 'required|alpha',
        'age' => 'required|int',
    ]);

    expectt($validator->fails())->toBeTrue();
    expectt($validator->errors()->all())->toHaveCount(2);
    expectt($validator->errors('email'))->toBeArray();
    expectt($validator->errors('age'))->toBeNull();
});

it('Skip validator if value not exits and rule is not includes REQUIRED', function () {
    $validator = Validator::make([
        'name' => '647'
    ]);

    $validator->validate([
        'name' => ['number'],
        'age' => 'int|in:29,18'
    ]);

    expectt($validator->errors('age'))->toBeNull();
    expectt($validator->fails())->toBeFalse();
});

it('Acces to inputs has object property', function () {
    $validator = Validator::make([
        'email' => 'example.com',
        'name' => '647',
        'age' => 18,
    ]);

    expectt($validator->email)->toBe('example.com');
    expectt($validator->age)->toBe(18);
});

it('Throw error when rule is not registered', function () {
    $validator = new Validator([]);

    $validator->validate([
        'maybe' => 'not-found|int|required',
    ]);
})->throws(RuleNotFoundException::class);
