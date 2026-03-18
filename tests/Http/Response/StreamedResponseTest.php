<?php

use Swilen\Http\Common\Http;
use Swilen\Http\Request;
use Swilen\Http\Response\StreamedResponse;

uses()->group('Http', 'Response');

it('Espect \Response instance created succesfully and is instance of \Swilen\Http\Response\StreamedResponse', function () {
    $response = new StreamedResponse(function () {});
    expectt($response)->toBeObject();
    expectt($response)->toBeInstanceOf(StreamedResponse::class);
    expectt($response->getStatusCode())->toBe(Http::OK);
    expectt($response->getBody())->toBeNull();
});

it('Expect StreamedResponse() send content as json', function () {
    /** @var StreamedResponse $response */
    list($response, $content) = getBuffer(function () {
        return (new StreamedResponse(function () {
            echo json_encode(['hello' => 'World']);
        }, 200, [
            'Content-Type' => 'application/json',
        ]))->prepare(Request::make(''))->terminate();
    });
    expectt($response->headers->get('Content-Type'))->toBeGreaterThanOrEqual('application/json');
    expectt($response->isOk())->toBeTrue();
    expectt($response->getBody())->toBeNull();
    expectt($content)->toBe('{"hello":"World"}');
});

it('Expect StreamedResponse() send content with sleep', function () {
    /** @var StreamedResponse $response */
    list($response, $content) = getBuffer(function () {
        return (new StreamedResponse(function () {
            echo 'First,';
            flush();
            usleep(150);
            echo 'Second';
            flush();
        }))->prepare(Request::make(''))->terminate();
    });
    expectt($response->isOk())->toBeTrue();
    expectt($response->getBody())->toBeNull();
    expectt($content)->toBe('First,Second');
});

it('Expect StreamedResponse() send headers and callback call once', function () {
    $callable = new StreamedCallbackStub();
    /** @var StreamedResponse $response */
    list($response, $content) = getBuffer(function () use ($callable) {
        return (new StreamedResponse($callable))->prepare(Request::make(''))->terminate()->terminate();
    });
    expectt($response->isOk())->toBeTrue();
    expectt($response->getBody())->toBeNull();
    expectt($content)->toBe('Hello');
    expectt($callable->call)->toBe(1);
});

class StreamedCallbackStub
{
    public $call = 0;

    public function __invoke()
    {
        ++$this->call;

        echo 'Hello';
    }
}
