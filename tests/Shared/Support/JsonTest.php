<?php

use Swilen\Shared\Support\Arrayable;
use Swilen\Shared\Support\Json;
use Swilen\Shared\Support\Jsonable;

uses()->group('Http', 'Transform');

it('Correct transform json content', function () {
    $transformer = new Json(['foo', 'bar']);

    expectt($transformer->encode())->toBeJson();

    $decoder = new Json('{"name": "foo"}');

    expectt($decoder->decode())->toBeObject();
    expectt($decoder->decode(true))->toBeArray();
    expectt($decoder->decode(true))->toBe([
        'name' => 'foo',
    ]);

    $decoder = new Json(true);

    expectt($decoder->encode())->toBeTruthy();
});

it('Throw content for transform is invalid', function () {
    $decoder = new Json('{"name": "foo"');

    expectt($decoder->decode())->toBeArray();
})->throws(JsonException::class);

it('Throw content for transform is invalid encoded', function () {
    $decoder = new Json('Parâmetros de consulta inválidos');

    expectt($decoder->decode(true))->toBeArray();
})->throws(JsonException::class);

it('Throw content for transform is invalid decoded', function () {
    $file = fopen(getReadableFileStub(), 'r');

    $decoder = new Json($file);

    expectt($decoder->encode())->toBeArray();

    fclose($file);
})->throws(JsonException::class, 'Failed encode json: A value of a type that cannot be encoded was given.');

it('Should type is valid json transform', function () {
    expectt(Json::shouldBeJson([]))->toBeTrue();
    expectt(Json::shouldBeJson(new stdClass()))->toBeTrue();
    expectt(Json::shouldBeJson(new UserStoreStub()))->toBeTrue();
    expectt(Json::shouldBeJson(new JsonableStub()))->toBeTrue();

    expectt(Json::shouldBeJson(20))->toBeFalse();
    expectt(Json::shouldBeJson('string'))->toBeFalse();
});

it('Morph content to json', function () {
    expectt(Json::morphToJson([]))->toBeJson();
    expectt(Json::morphToJson(new stdClass()))->toBeJson();
    expectt(Json::morphToJson(new UserStoreStub()))->toBeJson();
    expectt(Json::morphToJson(new JsonableStub()))->toBeJson();
    expectt(Json::morphToJson(new JsonSerializableStub()))->toBeJson();
});

class UserStoreStub implements Arrayable
{
    public function toArray()
    {
        return [];
    }
}

class JsonableStub implements Jsonable
{
    public function toJson($options = 0)
    {
        return json_encode(['foo'], $options);
    }
}

class JsonSerializableStub implements JsonSerializable
{
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['foo'];
    }
}
