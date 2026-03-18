<?php

use Swilen\Arthropod\Exception\JsonFormatter;
use Swilen\Http\Exception\HttpException;

uses()->group('Application');

it('Excepetion formatter as json', function () {
    $formatter = new JsonFormatter(new Exception(), true);

    expectt($formatter->format())->toBeJson();
});

it('Get different formats in whether or not it is debug mode', function () {
    $exception = new Exception('Exception Message', 200);

    $formatter = new JsonFormatter($exception, true);

    $formatted = $formatter->formatExceptionFragment($exception);
    expectt($formatted)->toBeArray();
    expectt($formatted)->toHaveKeys(['type', 'message', 'code', 'file', 'trace']);
    expectt($formatted)->not->toHaveKey('args');
    expectt($formatted['code'])->toBe(200);

    $formatter = new JsonFormatter($exception, false);
    $formatted = $formatter->formatExceptionFragment($exception);

    expectt($formatted)->toHaveCount(2);
    expectt($formatted)->toHaveKeys(['type', 'message']);
    expectt($formatted)->toBe([
        'type' => get_class($exception),
        'message' => 'Internal Server Error',
    ]);
});

it('Get message from format if exceptio is HttpException', function () {
    $message = 'This message is fro http exception';
    $exception = new HttpException($message, 401);

    $formatter = new JsonFormatter($exception, true);

    $formatted = $formatter->formatExceptionFragment($exception);
    expectt($formatted)->toBeArray();
    expectt($formatted)->toHaveKeys(['type', 'message', 'code', 'file', 'trace']);
    expectt($formatted['code'])->toBe(401);

    $formatter = new JsonFormatter($exception, false);
    $formatted = $formatter->formatExceptionFragment($exception);

    expectt($formatted)->toHaveCount(2);
    expectt($formatted)->toHaveKeys(['type', 'message']);
    expectt($formatted)->toBe([
        'type' => get_class($exception),
        'message' => $message,
    ]);
});
