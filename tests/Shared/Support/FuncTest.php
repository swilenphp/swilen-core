<?php

use Swilen\Shared\Support\Func;

uses()->group('Support', 'Func');

it('Unwrap closure', function () {
    $closure = function () {
        return 10;
    };

    expectt(Func::unwrap($closure))->toBe(10);
    expectt(Func::unwrap('nothing'))->toBe('nothing');
});

it('Pipe with closures', function () {
    $shipping = function (int $value) {
        return $value + 10;
    };

    $tax = new class {
        public function __invoke(int $result)
        {
            return $result + ($result / 10);
        }
    };

    expectt(Func::pipe(20, $shipping))->toBe(30);
    expectt(Func::pipe(10, $shipping, function (int $result) {
        return $result / 5;
    }))->toBe(4);

    expectt(Func::pipe(20, $shipping, $tax))->toBe(33);
});
