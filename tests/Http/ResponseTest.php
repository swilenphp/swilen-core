<?php

use Swilen\Http\Common\HttpStatus;
use Swilen\Http\Common\Method;
use Swilen\Http\Request;
use Swilen\Http\Response;

uses()->group('Http', 'Response');

it('Espect \Response instance created succesfully and is instance of \Swilen\Http\Response', function () {
    $response = new Response();
    expectt($response)->toBeObject();
    expectt($response)->toBeInstanceOf(Response::class);
    expectt($response->getStatusCode())->toBe(HttpStatus::OK->value);
    expectt($response->getBody())->toBeNull();
});

it('Response with empty content', function () {
    $response = new Response('testing', HttpStatus::NO_CONTENT->value);
    $prepared = $response->prepare(Request::make('/'));

    expectt($prepared->getBody())->toBeNull();
    expectt($prepared->getStatusCode())->toBe(HttpStatus::NO_CONTENT->value);
    expectt($prepared->isEmpty())->toBeTrue();

    $response = new Response('content-ignored');
    expectt($response->getBody())->not->toBeNull();
    expectt($response->isEmpty())->toBeFalse();

    $response->setNotModified();

    expectt($response->isEmpty())->toBeTrue();
    expectt($response->getBody())->toBeNull();

    $response = new Response('omited');
    expectt($response->getBody())->not->toBeNull();

    $response->prepare(Request::make('', Method::HEAD->value));
    expectt($response->getBody())->toBeNull();
});

it('Throw error when insert invalid status code', function () {
    $response = new Response();

    $response->withStatus(10);
})->throws(InvalidArgumentException::class, 'The HTTP status code "10" is not valid.');

it('Work with status codes', function () {
    $response = new Response();

    expectt($response->isSuccessful())->toBeTrue();
    expectt($response->isOk())->toBeTrue();
    expectt($response->isServerError())->toBeFalse();

    $response = $response->withStatus(100);
    expectt($response->isInformational())->toBeTrue();
    expectt($response->isOk())->toBeFalse();

    $response = $response->withStatus(HttpStatus::NO_CONTENT->value);
    expectt($response->isEmpty())->toBeTrue();
    expectt($response->isOk())->toBeFalse();

    $response = $response->withStatus(HttpStatus::MOVED_PERMANENTLY->value);
    expectt($response->isRedirection())->toBeTrue();
    expectt($response->isOk())->toBeFalse();

    $response = $response->withStatus(400);
    expectt($response->isClientError())->toBeTrue();
    expectt($response->isOk())->toBeFalse();

    $response = $response->withStatus(HttpStatus::NOT_FOUND->value);
    expectt($response->isNotFound())->toBeTrue();
    expectt($response->isClientError())->toBeTrue();

    $response = $response->withStatus(HttpStatus::INTERNAL_SERVER_ERROR->value);
    expectt($response->isServerError())->toBeTrue();
    expectt($response->isClientError())->toBeFalse();
    expectt($response->isOk())->toBeFalse();

    $response = $response->withStatus(403);
    expectt($response->isForbidden())->toBeTrue();
    expectt($response->isOk())->toBeFalse();
});

it('Status text in response', function () {
    $response = new Response();
    expectt($response->getReasonPhrase())->toBe('OK');

    $response->withStatus(100);
    expectt($response->getReasonPhrase())->toBe('Continue');
});

it('Insert header succesfully', function () {
    $response = new Response();
    $response->withHeader('Fo', 'bar');
    expectt($response->headers->get('Fo'))->toBe('bar');

    $response->withHeaders([
        'bar-1' => 'foo',
        'foo-1' => 'bar',
    ]);
    expectt($response->headers->all())->toHaveKeys(['Fo', 'bar-1', 'foo-1']);

    $response = $response->header('X-Key', 'x-value');
    expectt($response->headers->has('X-Key'))->toBeTrue();

    $response = $response->headers([
        'x-header' => 'data',
        'x-token' => 'data-token',
    ]);

    expectt($response->headers->has('x-header'))->toBeTrue();
    expectt($response->headers->has('x-token'))->toBeTrue();
});

it('Interact with response body', function () {
    $response = new Response();

    expectt($response->getBody())->toBeNull();
    $response = $response->withBody('test');
    expectt($response->getBody())->toBe('test');
});

it('Body to send client', function () {
    /** @var Response $response */
    list($response, $content) = getBuffer(function () {
        return (new Response('simple-text'))->prepare(Request::make(''))->terminate();
    });

    expectt($content)->toBe('simple-text');
    expectt($response->headers->get('Content-Type'))->toBeIn(['text/html', 'text/html; charset=utf-8']);
    expectt($response->getBody())->toBe('simple-text');
});

it('Content-Type and charset fixed in response', function () {
    $response = new Response(null);

    expectt($response->headers->get('Content-Type'))->toBeNull();

    $response->withHeader('Content-Type', 'text/html');
    expectt($response->headers->get('Content-Type'))->not->toBeNull();

    $response->prepare(Request::make(''));
    expectt($response->headers->get('Content-Type'))->toMatch('/^text\/html;\scharset=+/');
});

it('Fix content length when found Transfer-Encoding present', function () {
    $response = new Response(null);

    $response->withHeader('Transfer-Encoding', 'gzip')->prepare(Request::make(''));
    expectt($response->headers->has('Content-Length'))->toBeFalse();
});

it('Fix cache headers based in server protocol', function () {
    $response = new Response();

    expectt($response->getProtocolVersion())->toBe('1.0');
    $response->prepare(Request::make(''));
    expectt($response->getProtocolVersion())->toBe('1.1');

    $response = (new Response(null, 200, [
        'Cache-Control' => 'no-cache',
    ]))->prepare(Request::make('/'));

    expectt($response->hasHeader('pragma'))->toBeTrue();
    expectt($response->hasHeader('expires'))->toBeTrue();
});

it('finish request with fastcgi_finish_request', function () {
    list($response, $content) = getBuffer(function () {
        mockFinishRequestFunc();
        return (new Response())->prepare(Request::make('/'))->terminate();
    });

    expectt($content)->toBe('');
    expectt($response->getBody())->toBeNull();
});

function mockFinishRequestFunc()
{
    if (!function_exists('fastcgi_finish_request')) {
        function fastcgi_finish_request()
        {
            return null;
        }
    }
}
