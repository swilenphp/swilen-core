<?php

use Swilen\Security\Hashing\Hash;

uses()->group('Security', 'Hash');

it('Hash::make creates a valid bcrypt hash', function () {
    $password = 'testPassword123';
    $hash = Hash::make($password);

    expect($hash)->toBeString();
    expect($hash)->toMatch('/^\$2y\$10\$/');
    expect(Hash::verify($password, $hash))->toBeTrue();
});

it('Hash::verify correctly validates passwords', function () {
    $password = 'testPassword123';
    $wrongPassword = 'wrongPassword';
    $hash = Hash::make($password);

    expect(Hash::verify($password, $hash))->toBeTrue();
    expect(Hash::verify($wrongPassword, $hash))->toBeFalse();
});

it('Hash::verify returns false for invalid hash', function () {
    expect(Hash::verify('anything', 'not a hash'))->toBeFalse();
});

it('Hash::make accepts cost options', function () {
    $password = 'testPassword123';
    $hash = Hash::make($password, ['cost' => 12]);

    expect($hash)->toBeString();
    expect($hash)->toMatch('/^\$2y\$12\$/');
    expect(Hash::verify($password, $hash))->toBeTrue();
});

it('Hash::make works with empty options', function () {
    $password = 'testPassword123';
    $hash = Hash::make($password, []);

    expect($hash)->toBeString();
    expect($hash)->toMatch('/^\$2y\$10\$/');
    expect(Hash::verify($password, $hash))->toBeTrue();
});
