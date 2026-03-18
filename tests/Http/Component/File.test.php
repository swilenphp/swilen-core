<?php

use Swilen\Http\Component\File\File;
use Swilen\Http\Component\File\UploadedFile;
use Swilen\Http\Exception\FileNotFoundException;

uses()->group('Http', 'File');

it('File Content is valid', function () {
    $file = new UploadedFile(__DIR__ . '/../__fixtures__/test.txt', 'test.txt');

    expectt(trim($file->getContent()))->toBe('No content for movement this file');
    expectt($file->getMimeType())->toBe('text/plain');
    expectt($file->getExtension())->toBe('txt');
    expectt($file->getClientOriginalExtension())->toBe('txt');
    expectt($file->getClientOriginalName())->toBe('test.txt');
    expectt($file->getFilename())->toBe('test.txt');
});

it('Generate error when file does not exist', function () {
    new UploadedFile(__DIR__ . '/__fixtures__/not-found.txt', 'not-found.txt');
})->throws(FileNotFoundException::class);

it('Verify if file is instance of SplFileInfo', function () {
    $file = new File(__DIR__ . '/../__fixtures__/test.txt', true);

    expectt($file)->toBeInstanceOf(SplFileInfo::class);
});
