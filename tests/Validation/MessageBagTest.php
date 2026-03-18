<?php

use Swilen\Validation\MessageBag;

uses()->group('Validation', 'MessageBag');

it('Message bag instance created without errors', function () {
    $messages = new MessageBag([]);

    expectt($messages->count())->toBe(0);
    expectt($messages)->toBeIterable();
});

it('Iterate messages bag as array', function () {
    $messages = new MessageBag([
        'key' => [
            'message',
            'second',
        ],
    ]);

    foreach ($messages as $key => $message) {
        expectt($key)->toBe('key');
        expectt($message)->toBe(['message', 'second']);
    }

    expectt($messages)->toBeIterable();
    expectt($messages)->toBeInstanceOf(IteratorAggregate::class);
});

it('Get first message from messages bag or null if not exists', function () {
    $messages = new MessageBag([
        'test' => [
            'First test',
            'Second test',
        ],
    ]);

    expectt($messages->first('test'))->toBe('First test');
    expectt($messages->get('test'))->toBe(['First test', 'Second test']);
    expectt($messages->first('not-found'))->toBeNull();
});

it('Get message formatted', function () {
    $messages = new MessageBag([
        'test' => [
            'First test',
            'Second test',
        ],
    ]);

    expectt($messages->all('$ :message'))->toBe([
        'test' => [
            '$ First test',
            '$ Second test',
        ],
    ]);

    expectt($messages->firstOfAll('1 :message'))->toBe([
        'test' => '1 First test',
    ]);

    expectt($messages->get('test', '<li> :message </li>'))->toBe([
        '<li> First test </li>',
        '<li> Second test </li>',
    ]);

    expectt($messages->first('test', 'TEST :message'))->toBe('TEST First test');
});

it('Message bag is empty and is not empty', function () {
    $messages = new MessageBag([
        'found' => [
            'Is not empty',
        ],
    ]);

    expectt($messages->isNotEmpty())->toBeTrue();
    expectt($messages->isEmpty())->toBeFalse();
});

it('Current messages keys are exists and is enumerable and deletable', function () {
    $messages = new MessageBag([
        'found' => [
            'Is not empty',
        ],
        'other' => [
            'found Error',
        ],
    ]);

    expectt($messages->keys())->toBe(['found', 'other']);
    expectt($messages->has('other'))->toBeTrue();
    expectt($messages->has('not-found'))->toBeFalse();

    $messages->remove('found', 'other');

    expectt($messages->count())->toBe(0);
    expectt($messages->all())->not->toHaveKeys(['found', 'other']);
});

it('Add new messages to message bag', function () {
    $messages = new MessageBag([]);

    expectt($messages->count())->toBe(0);

    $messages->add('first_key', 'First Message');

    expectt($messages->count())->toBe(1);
    expectt($messages->get('first_key'))->toBe(['First Message']);

    $messages->add('first_key', 'Second Message');

    expectt($messages->get('first_key'))->toHaveCount(2);
    expectt($messages->all())->toHaveCount(1);
});

it('Merge new messages to existing message bag', function () {
    $messages = new MessageBag([
        'initial' => [
            'message',
        ],
    ]);

    expectt($messages->all())->toHaveCount(1);

    $messages->merge([
        'second' => [
            'another-message',
        ],
    ]);

    expectt($messages->all())->toHaveCount(2);
    expectt($messages->keys())->toBe(['initial', 'second']);
    expectt($messages->getMessages())->toBe([
        'initial' => [
            'message',
        ],
        'second' => [
            'another-message',
        ],
    ]);
});

it('Interact with Message Bag as array with \ArrayAcces implementation', function () {
    $messages = new MessageBag([]);

    $messages['key'] = 'message';
    expectt($messages['key'])->toBe(['message']);

    $messages['key'] = 'another-message';
    expectt($messages['key'])->toBe(['message', 'another-message']);

    expectt(isset($messages['key']))->toBeTrue();
    expectt(isset($messages['random']))->toBeFalse();

    unset($messages['key']);

    expectt(isset($messages['key']))->toBeFalse();
    expectt(count($messages))->toBe(0);
});

it('Message bag imlement Arrayable', function () {
    $messages = new MessageBag([]);

    expectt($messages->toArray())->toBeArray();
});
