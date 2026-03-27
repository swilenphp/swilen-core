<?php

use Swilen\Arthropod\Application;
use Swilen\Config\Repository;
use Swilen\Container\Container;
use Swilen\Http\Component\File\File;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Http\Response\BinaryFileResponse;
use Swilen\Http\Response\JsonResponse;
use Swilen\Http\Response\StreamedResponse;
use Swilen\Routing\Contract\ResponseFactory;
use Swilen\Shared\Arthropod\Application as ArthropodApplication;

uses()->group('Application', 'Helpers');

it('Application core helper app()', function () {
    $app = new Application();
    expectt(app())->toBeInstanceOf(ArthropodApplication::class);
    expectt(app())->toBeInstanceOf(Application::class);
    expectt(app())->toBeInstanceOf(Container::class);
    expectt(app('app'))->toBeInstanceOf(Application::class);

    expectt(app(MakeClassAppHelperStub::class))->toBeInstanceOf(MakeClassAppHelperStub::class);

    $app->setInstance(null);
});

it('Response helper is instaceof Routing\ResponsiFactory', function () {
    $app = new Application();

    expectt(response())->toBeInstanceOf(ResponseFactory::class);

    expectt(response()->send())->toBeInstanceOf(Response::class);
    expectt(response()->file(new File(getReadableFileStub())))->toBeInstanceOf(BinaryFileResponse::class);
    expectt(response()->download(new File(getReadableFileStub())))->toBeInstanceOf(BinaryFileResponse::class);
    expectt(response()->json())->toBeInstanceOf(JsonResponse::class);
    expectt(response()->stream(function () {
    }))->toBeInstanceOf(StreamedResponse::class);

    $app[ResponseFactory::class] = new stdClass();

    expectt(response())->toBeInstanceOf(stdClass::class);

    $app->setInstance(null);
});

it('Request helper is instaceof Http\Request', function () {
    $app = new Application();

    $app->instance('request', Request::capture());

    expectt(request())->toBeInstanceOf(Request::class);
    expectt(request()->getMethod())->toBe('GET');
});

it('Work with application paths', function () {
    $app = new Application();

    $app->useBasePath(__DIR__);

    expectt(base_path())->toBe(__DIR__);

    $app->useAppPath('test');

    expectt(app_path())->toBe(__DIR__ . DIRECTORY_SEPARATOR . 'test');
    expectt(storage_path())->toBe(__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'storage');
    expectt(storage_path('test'))->toBe(__DIR__ . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'test');

    $app->setInstance(null);
});

it('Work with application config', function () {
    $app = new Application();

    $app->instance('config', new Repository(['item' => 'test']));

    expectt(config())->toBeInstanceOf(Repository::class);
    expectt(config('item'))->toBe('test');
    expectt(config('not-found', 'bar'))->toBe('bar');

    $app->setInstance(null);
});

it('env helper get environment variables', function () {
    expectt(env('NULL'))->toBeNull();
    expectt(env('REQUEST_METHOD', false))->toBeFalse();
});

it('Work with tap helper', function () {
    $object = new stdClass();

    $object->makeit = 'test';
    $value          = false;

    $result = tap($object, function ($object) use (&$value) {
        $value = $object->makeit;
    });

    expectt($value)->toBe('test');
    expectt($result)->toBeInstanceOf(stdClass::class);
});

it('Cannot redeclare helper functions', function () {
    if (!function_exists('app')) {
        function app()
        {
            return true;
        }
    }

    expectt(app())->not->toBeTrue();

    if (!function_exists('tap')) {
        function tap()
        {
            return true;
        }
    }

    expectt(tap('Hola', function () {
    }))->not->toBeTrue();
});

class MakeClassAppHelperStub
{
}
