<?php

use Swilen\Http\Common\HttpStatus;
use Swilen\Http\Request;
use Swilen\Http\Response\JsonResponse;

uses()->group('Http', 'Response');

it('Espect \Response instance created succesfully and is instance of \Swilen\Http\Response\JsonResponse', function () {
    $response = new JsonResponse();
    expectt($response)->toBeObject();
    expectt($response)->toBeInstanceOf(JsonResponse::class);
    expectt($response->getStatusCode())->toBe(HttpStatus::OK->value);
});

it('Expect JsonResponse() send content as json', function () {
    /** @var JsonResponse $response */
    list($response, $content) = getBuffer(function () {
        return (new JsonResponse(['hello' => 'World']))->prepare(Request::make(''))->terminate();
    });
    expectt($response->headers->get('Content-Type'))->toMatch('/^application\/json+/');
    expectt($response->getStatusCode())->toBe(HttpStatus::OK->value);
    expectt($response->getBody())->toBeJson();
    expectt($content)->toBe('{"hello":"World"}');
});

it('Prevent override Content-Type in header', function () {
    $response = new JsonResponse([], 200, ['Content-Type' => 'text/html']);

    expectt($response->headers->get('Content-Type'))->toMatch('/^application\/json+/');
});

it('Send new JsonResponse from jsonString', function () {
    $response = JsonResponse::fromJson('{"bar":"foo"}');

    expectt($response->getBody())->toBe('{"bar":"foo"}');
    expectt($response->isOk())->toBeTrue();
});

it('Interact with json body', function () {
    $response = new JsonResponse();

    expectt($response->getBody())->toBeJson();
    expectt($response->getBody())->toBe('{}');

    $response->withBody([]);
    expectt($response->getBody())->toBe('[]');
});


it('Throw TypeError when passing data as invalid data type', function () {
    new JsonResponse([], 200, [], true);
})->throws(TypeError::class, '"Swilen\Http\Response\JsonResponse::setBody": If $json is set to true, argument $data must be a string, "array" given.');
