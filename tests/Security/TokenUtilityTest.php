<?php

use Swilen\Crypto\NanoId;
use Swilen\Crypto\Opaque;
use Swilen\Crypto\Uuid;

uses()->group('Security', 'CryptoIdentifier');

it('generates NanoId with default length', function () {
    $nanoid = NanoId::new();

    expectt(\strlen((string) $nanoid))->toBe(21);
    expectt(\preg_match('/^[a-zA-Z0-9_-]+$/', (string) $nanoid))->toBe(1);
});

it('generates NanoId with custom length', function () {
    $nanoid = NanoId::new(10);

    expectt(\strlen((string) $nanoid))->toBe(10);
});

it('generates unique NanoIds', function () {
    $nanoid1 = NanoId::new();
    $nanoid2 = NanoId::new();

    expectt((string) $nanoid1 !== (string) $nanoid2)->toBeTrue();
});

it('NanoId equals compares correctly', function () {
    $nanoid = NanoId::new();

    expectt($nanoid->equals((string) $nanoid))->toBeTrue();
    expectt($nanoid->equals('different'))->toBeFalse();
});

it('NanoId can be reconstructed from string', function () {
    $nanoid = NanoId::new();
    $from = NanoId::from((string) $nanoid);

    expectt($nanoid->equals($from))->toBeTrue();
});

it('generates valid UUID', function () {
    $uuid = Uuid::new();

    expectt(\preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        (string) $uuid
    ))->toBe(1);
});

it('generates unique UUIDs', function () {
    $uuid1 = Uuid::new();
    $uuid2 = Uuid::new();

    expectt((string) $uuid1 !== (string) $uuid2)->toBeTrue();
});

it('UUID equals compares correctly', function () {
    $uuid = Uuid::new();

    expectt($uuid->equals((string) $uuid))->toBeTrue();
    expectt($uuid->equals('different'))->toBeFalse();
});

it('UUID can be reconstructed from string', function () {
    $uuid = Uuid::new();
    $from = Uuid::from((string) $uuid);

    expectt($uuid->equals($from))->toBeTrue();
});

it('generates Opaque token with default bytes', function () {
    $opaque = Opaque::new();

    expectt(\strlen((string) $opaque))->toBe(64);
});

it('generates Opaque token with custom bytes', function () {
    $opaque = Opaque::new(16);

    expectt(\strlen((string) $opaque))->toBe(32);
});

it('generates unique Opaque tokens', function () {
    $opaque1 = Opaque::new();
    $opaque2 = Opaque::new();

    expectt((string) $opaque1 !== (string) $opaque2)->toBeTrue();
});

it('Opaque equals compares correctly', function () {
    $opaque = Opaque::new();

    expectt($opaque->equals((string) $opaque))->toBeTrue();
    expectt($opaque->equals('different'))->toBeFalse();
});

it('Opaque can be reconstructed from string', function () {
    $opaque = Opaque::new();
    $from = Opaque::from((string) $opaque);

    expectt($opaque->equals($from))->toBeTrue();
});

it('Identifier equals is constant time', function () {
    $opaque = Opaque::new();
    $same = (string) $opaque;
    $different = str_repeat('a', strlen($same));

    expectt($opaque->equals($same))->toBeTrue();
    expectt($opaque->equals($different))->toBeFalse();
});

it('Identifier equals handles different types', function () {
    $opaque = Opaque::new();

    expectt($opaque->equals(null))->toBeFalse();
    expectt($opaque->equals(123))->toBeFalse();
    expectt($opaque->equals((string) $opaque))->toBeTrue();
});

it('Identifier implements JsonSerializable', function () {
    $opaque = Opaque::new();

    expectt(\json_encode($opaque))->toBe('"' . (string) $opaque . '"');
});

it('Identifier implements Stringable', function () {
    $opaque = Opaque::new();
    $str = (string) $opaque;

    expectt($str)->toBeString();
});
