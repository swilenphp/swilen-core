<?php

use Swilen\Shared\Support\Arr;

uses()->group('Support', 'Arr');

it('Detect is accesible when is array or implement \ArrayAccess', function () {
    $arrayAccess = makeArrayAccess([]);

    expectt(Arr::accessible($arrayAccess))->toBeTrue();
    expectt(Arr::accessible([]))->toBeTrue();
    expectt(Arr::accessible('String'))->toBeFalse();
});

it('Detect key exists in array or offsetExist of \ArrayAccess implemention', function () {
    $arrayAccess = makeArrayAccess(['todo' => 'fo']);

    expectt(Arr::exists($arrayAccess, 'todo'))->toBeTrue();
    expectt(Arr::exists(['todo' => 'fo'], 'todo'))->toBeTrue();
    expectt(Arr::exists($arrayAccess, 'nothing'))->toBeFalse();
    expectt(Arr::exists(['todo' => false], 'nothing'))->toBeFalse();
});

it('Return default if key not exits or not provide', function () {
    $arrayAccess = makeArrayAccess(['todo' => 'fo']);

    // NOT EXISTS OR INNACESIBLE
    expectt(Arr::get($arrayAccess, '404', 'FOO'))->toBe('FOO');
    expectt(Arr::get('STRING', 'INNACESIBLE', '__default'))->toBe('__default');

    // EXISTS BUT NO KEY WAS PROVIDED
    expectt(Arr::get(['todo' => 'fo'], null, 'todo'))->toBe(['todo' => 'fo']);

    // RETURN EXISTS
    expectt(Arr::get($arrayAccess, 'todo'))->toBe('fo');
});

it('Verify if key has in given array', function () {
    $arrayAccess = makeArrayAccess(['todo' => 'fo']);
    $array       = ['data' => ['fo' => 'fo']];

    // WHEN IS EMPTY ARRAY AND KEY
    expectt(Arr::has([], []))->toBeFalse();

    expectt(Arr::has($arrayAccess, 'todo'))->toBeTrue();

    expectt(Arr::has($array, 'data.fo'))->toBeTrue();
    expectt(Arr::has($array, 'data.nothing'))->toBeFalse();
});

it('Set array with reference', function () {
    $arrayAccess = makeArrayAccess([]);
    $empty       = [];

    Arr::set($empty, null, ['data' => 'fo']);
    Arr::set($arrayAccess, null, ['data' => 'fo']);

    expectt($empty)->toHaveKey('data');
    expectt($arrayAccess)->toHaveKey('data');

    Arr::set($empty, 'data', ['FOO' => 'BAR']);
    Arr::set($arrayAccess, 'data', ['FOO' => 'BAR']);

    expectt($empty)->toBe(['data' => ['FOO' => 'BAR']]);
    expectt($arrayAccess)->toBe(['data' => ['FOO' => 'BAR']]);
});

it('Remove array values based in keys', function () {
    $array = [
        'todo' => 'ok',
        'completed' => true,
        'date' => date('Y-m-d'),
    ];

    expectt($array)->toHaveKeys(['todo', 'completed', 'date']);
    $array = Arr::except($array, ['todo', 'complete']);

    expectt($array)->not->toHaveKeys(['todo', 'complete']);
    expectt($array)->toHaveKey('date');
});

it('Morph to array given mixed type', function () {
    expectt(Arr::morph([]))->toBeArray();
    expectt(Arr::morph(new UserStoreStub()))->toBeArray();
    expectt(Arr::morph(new UserStoreJsonSerializableStub()))->toBeArray();
    expectt(Arr::morph(new \ArrayObject()))->toBeArray();
    expectt(Arr::morph(new \stdClass()))->toBeArray();
});

class UserStoreJsonSerializableStub implements JsonSerializable
{
    public function jsonSerialize(): mixed
    {
        return [];
    }
}

function makeArrayAccess(array $items = [])
{
    return new class($items) implements ArrayAccess {
        public $items = [];

        public function __construct(array $items)
        {
            $this->items = $items;
        }

        #[ReturnTypeWillChange]
        public function offsetExists($offset)
        {
            return isset($this->items[$offset]);
        }

        #[ReturnTypeWillChange]
        public function offsetSet($offset, $value)
        {
            $this->items[$offset] = $value;
        }

        #[ReturnTypeWillChange]
        public function offsetGet($offset)
        {
            return $this->items[$offset];
        }

        #[ReturnTypeWillChange]
        public function offsetUnset($offset)
        {
            unset($this->items[$offset]);
        }
    };
}
