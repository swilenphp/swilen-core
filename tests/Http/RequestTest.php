<?php

use Swilen\Http\Component\File\UploadedFile;
use Swilen\Http\Exception\HttpNotOverridableMethodException;
use Swilen\Http\Request;

uses()->group('Http', 'Request');

beforeEach(function () {
    $this->request = Request::capture();
});

it('Test if request is created without errors', function () {
    expectt($this->request)->toBeObject();
    expectt($this->request)->toBeInstanceOf(Request::class);
});

/*
 * @param \Swilen\Http\Request $request
 */
it('Request instance created with empty attributes', function () {
    $request = new Request();

    expectt($request->server->all())->toBeArray()->toBeEmpty();
    expectt($request->headers->all())->toBeArray()->toBeEmpty();
    expectt($request->files->all())->toBeArray()->toBeEmpty();
    expectt($request->request->all())->toBeArray()->toBeEmpty();
    expectt($request->query->all())->toBeArray()->toBeEmpty();
    expectt($request->getBody())->toBeFalsy();
    expectt($request->getMethod())->toBe('GET');
    expectt($request->isMethodSafe())->toBeTrue();
    expectt($request->getInputSource()->all())->toBeEmpty();
});

it('Request instance created with superglobals from static method', function () {
    $request = Request::capture();

    expectt($request->server->all())->toBeArray()->not->toBeEmpty();
    expectt($request->headers->all())->toBeArray()->toBeEmpty();
    expectt($request->request->all())->toBeArray()->toBeEmpty();
    expectt($request->query->all())->toBeArray()->toBeEmpty();
    expectt($request->getBody())->toBeFalsy();
    expectt($request->getMethod())->toBe('GET');
});

it('Access current request instance attributes with available methods', function () {
    $request = Request::capture();

    expectt($request->server('REQUEST_METHOD'))->toBeNull();
    expectt($request->server('REQUEST_METHOD', 'GET'))->toBe('GET');

    expectt($request->query('nothing', 'espect'))->toBe('espect');
    expectt($request->input('data-item', 'not-found'))->toBe('not-found');
    expectt($request->file('file-not-exists'))->toBeNull();

    expectt($request->all())->toBeArray()->toBeEmpty();
});

it('Access current request instance attributes ass array or object by magic methods', function () {
    $request = Request::capture();

    $request->property = null;

    expectt($request->property)->toBeNull();
    expectt($request['property'])->toBeNull();
    expectt(isset($request->property))->toBeTrue();
    expectt(isset($request['property']))->toBeTrue();

    $request['property'] = 'override';

    expectt($request->property)->toBe('override');

    // Delete a property as array
    unset($request['property']);

    expectt(isset($request['property']))->toBeFalse();

    $request->temp = 'allow';

    // Deelete a preperty as object
    unset($request->temp);

    expectt(isset($request->temp))->toBeFalse();
    expectt($request->all())->toBeEmpty();
});

it('Decode json request Content-Type has json', function () {
    /**
     * @var \Mockery\MockInterface|\Mockery\LegacyMockInterface|Request $request
     */
    $request = Mockery::mock(Request::class, [[], [], [], [], [], '{"test": true}']);
    $request = $request->makePartial();

    $request->shouldReceive('isJsonRequest')
        ->with()->andReturn(true);

    // expectt($request->getInputSource()->all())->toBe(['test' => true]);
    // expectt($request->getInputSource()->all())->toBe(['test' => true]);
    expectt($request->request->all())->toBeEmpty();
});

it('Make request succesfully with attributes', function () {
    $request = Request::make('/other?name=hola', 'GET', [
        'test' => 'name',
    ]);

    expectt($request->getMethod())->toBe('GET');
    expectt($request->getPathInfo())->toBe('/other');
    expectt($request->hasQueryString())->toBeTrue();
    expectt($request->query->get('name'))->toBe('hola');
    expectt($request->query->get('test'))->toBe('name');
    expectt($request->query->all())->not->toBeEmpty();
});

it('Make request succesfully as POST', function () {
    $request = Request::make('/other?name=hola', 'POST', [
        'test' => 'name',
    ]);

    expectt($request->getMethod())->toBe('POST');
    expectt($request->getPathInfo())->toBe('/other');
    expectt($request->hasQueryString())->toBeTrue();
    expectt($request->query->get('name'))->toBe('hola');
    expectt($request->request->get('test'))->toBe('name');
});

it('REQUEST_METHOD override succesfully', function () {
    $request = Request::make('put-request', 'POST', [], [], [
        'HTTP_X_METHOD_OVERRIDE' => 'PUT',
    ]);

    expectt($request->getMethod())->toBe('PUT');
    expectt($request->getRealMethod())->toBe('POST');
    expectt($request->getPathInfo())->toBe('/put-request');
    expectt($request->hasQueryString())->toBeFalse();

    $request->withMethod('PUT');

    expectt($request->getMethod())->toBe('PUT');
    expectt($request->getRealMethod())->toBe('PUT');
    expectt($request->isMethod('PUT'))->toBeTrue();
});

it('REQUEST_METHOD override failed', function () {
    $request = Request::make('put-request', 'POST', [], [], [
        'HTTP_X_METHOD_OVERRIDE' => 'GET',
    ]);

    expectt($request->getPathInfo())->toBe('/put-request');
    expectt($request->getMethod())->toBe('PUT');
})->throws(HttpNotOverridableMethodException::class);

it('Remove slashes support for REQUEST_URI', function () {
    expectt(Request::make('')->getPathInfo())->toBe('/');
    expectt(Request::make('hola///')->getPathInfo())->toBe('/hola');
    expectt(Request::make('/hola')->getPathInfo())->toBe('/hola');
});

it('Retrieve Bearer Token if exists', function () {
    $request = Request::make('test', 'GET', [], [], [
        'Authorization' => '',
    ]);

    expectt($request->bearerToken())->toBeNull();
    expectt($request->headers->get('Authorization'))->toBeEmpty();

    $request = Request::make('test', 'GET', [], [], [
        'Authorization' => 'Bearer xxx.xxx.xxxx.xxx',
    ]);

    expectt($request->bearerToken())->toBe('xxx.xxx.xxxx.xxx');
    expectt($request->headers->get('Authorization'))->toBe('Bearer xxx.xxx.xxxx.xxx');
});

it('Its filter when APP_BASE_URI provided', function () {
    $request = Request::make('app/other/value', 'GET', [], [], [
        'Authorization' => '',
    ]);

    $_ENV['APP_BASE_URI'] = 'app';

    expectt($request->getPathInfo())->toBe('/other/value');

    unset($_ENV['APP_BASE_URI']);
});

it('Detect content type', function () {
    $request = Request::make('app/other/value', 'GET', [], [], [
        'Authorization' => '',
    ]);

    expectt($request->isJsonRequest())->toBeFalse();
    $request->headers->set('Content-Type', 'application/json');
    expectt($request->isJsonRequest())->toBeTrue();

    expectt($request->isFormRequest())->toBeFalse();
    $request->headers->set('Content-Type', 'multipart/form-data');
    expectt($request->isFormRequest())->toBeTrue();

    expectt($request->hasHeader('Content-Type'))->toBeTrue();
});

it('Request pathInfo is cached', function () {
    $request = Request::make('app/other/value', 'GET', [], [], [
        'Authorization' => '',
    ]);

    expectt($request->getPathInfo())->toBe('/app/other/value');

    $request->server->set('REQUEST_URI', 'falsy');

    expectt($request->getPathInfo())->toBe('/app/other/value');
    expectt($request->server('REQUEST_URI'))->toBe('falsy');
});

it('Set user to request instance', function () {
    $request = Request::capture();

    expectt($request->user())->toBeNull();

    $request->withUser([
        'userId' => 24,
        'username' => 'foo',
    ]);

    expectt($request->user())->toBeArray();
});

it('Read content is stream or resource', function () {
    $file = fopen(getReadableFileStub(), 'rb');

    $request = new Request([], [], [], [], [], $file);

    expectt(trim($request->getBody()))->toBe('test');
});

it('Get file from request', function () {
    $request = new Request([], [], [
        'test-file' => [
            'name' => 'File.txt',
            'type' => 'plain/txt',
            'tmp_name' => getReadableFileStub(),
            'error' => UPLOAD_ERR_OK,
            'size' => 98174,
        ],
    ]);

    expectt($request->hasFile('test-file'))->toBeTrue();
    expectt($request->files->get('test-file'))->toBeInstanceOf(UploadedFile::class);
    expectt($request->hasFile('not-found'))->toBeFalse();
    expectt($request->files->get('not-found'))->toBeNull();
});
