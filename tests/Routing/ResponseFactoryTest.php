<?php

use Swilen\Http\Common\HttpStatus;
use Swilen\Http\Response;
use Swilen\Http\Response\BinaryFileResponse;
use Swilen\Http\Response\JsonResponse;
use Swilen\Http\Response\StreamedResponse;
use Swilen\Routing\ResponseFactory;

uses()->group('Routing');

it('Response Factory return correspond instance for the method', function () {
    $factory = new ResponseFactory();

    expectt($factory->send(''))->toBeInstanceOf(Response::class);
    expectt($factory->status(HttpStatus::OK->value))->toBeInstanceOf(Response::class);
    expectt($factory->json(''))->toBeInstanceOf(JsonResponse::class);
    expectt($factory->file(getReadableFileStub()))->toBeInstanceOf(BinaryFileResponse::class);
    expectt($factory->download(getReadableFileStub()))->toBeInstanceOf(BinaryFileResponse::class);
    expectt($factory->stream(function () {
    }))->toBeInstanceOf(StreamedResponse::class);

    $binary = new ResponseFactory();
    $filename = 'testing.txt';
    $binary = $binary->download(getReadableFileStub(), $filename);

    expectt($binary->headers->get('Content-Disposition'))->toBe('attachment; filename="' . $filename . '"');
});
