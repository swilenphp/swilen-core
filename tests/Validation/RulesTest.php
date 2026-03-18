<?php

use Swilen\Validation\Exception\MissingRequiredParameterException;
use Swilen\Validation\Rules\Alpha;
use Swilen\Validation\Rules\Beetwen;
use Swilen\Validation\Rules\Boolean;
use Swilen\Validation\Rules\Date;
use Swilen\Validation\Rules\Different;
use Swilen\Validation\Rules\Email;
use Swilen\Validation\Rules\Ext;
use Swilen\Validation\Rules\In;
use Swilen\Validation\Rules\Integer;
use Swilen\Validation\Rules\Ip;
use Swilen\Validation\Rules\Lowercase;
use Swilen\Validation\Rules\Max;
use Swilen\Validation\Rules\Min;
use Swilen\Validation\Rules\NotIn;
use Swilen\Validation\Rules\Nullable;
use Swilen\Validation\Rules\Number;
use Swilen\Validation\Rules\Regex;
use Swilen\Validation\Rules\Required;
use Swilen\Validation\Rules\RuleArray;
use Swilen\Validation\Rules\RuleObject;
use Swilen\Validation\Rules\Same;
use Swilen\Validation\Rules\Uppercase;
use Swilen\Validation\Rules\Url;

uses()->group('Validation', 'Rules');

it('Validate if the value is alphabetic', function ($invalid, $valid) {
    expectt((new Alpha($invalid))->validate())->toBeFalse();
    expectt((new Alpha($valid))->validate())->toBeTrue();
})->with([
    [20, 'absc'],
    ['m50.00a', 'muchos'],
    [[], 'mundo'],
]);

// it('Validate if the value is present beetwen', function ($invalid, $valid) {
//     expectt((new Beetwen($invalid))->validate())->toBeFalse();
//     expectt((new Beetwen($valid))->validate())->toBeTrue();
// })->with([
//     ['invalid', 'min'],
//     ['', true],
// ])->skip('Why do you need to implement');

it('Validate if the value is boolean', function ($invalid, $valid) {
    expectt((new Boolean($invalid))->validate())->toBeFalse();
    expectt((new Boolean($valid))->validate())->toBeTrue();
})->with([
    ['invalid', false],
    ['', true],
]);

it('Validate if the value is date', function ($invalid, $valid) {
    expectt((new Date($invalid))->validate())->toBeFalse();
    expectt((new Date($valid))->validate())->toBeTrue();
})->with([
    ['2022-80-50', '2022-10-08'],
    ['2010-50-50', '2022-10-08 10:25:15'],
    ['20100', '2022'],
    [[], '2010'],
]);

it('Validate if the value is date with format', function () {
    $validator = new Date();

    expectt($validator->validate())->toBeFalse();

    // invalids
    expectt($validator->setValue('2022-05-10')->setParameters(['Y-m'])->validate())->toBeFalse();
    expectt($validator->setValue('2022-25')->setParameters(['Y-m'])->validate())->toBeFalse();
    expectt($validator->setValue('2022-50')->setParameters(['Y-d'])->validate())->toBeFalse();

    // valids
    expectt($validator->setValue('2022-05-10')->setParameters(['Y-m-d'])->validate())->toBeTrue();
    expectt($validator->setValue('2022-12')->setParameters(['Y-m'])->validate())->toBeTrue();
    expectt($validator->setValue('2022-25')->setParameters(['Y-d'])->validate())->toBeTrue();
});

// it('Validate if the value is different another field', function ($invalid, $valid) {
//     expectt((new Different($invalid))->validate())->toBeFalse();
//     expectt((new Different($valid))->validate())->toBeTrue();
// })->with([
//     ['gmai.com', 'example@gmail.com'],
//     ['example.com', 'mira@gmail.com'],
// ])->skip('Why do you need to implement');

it('Validate if the value is email', function ($invalid, $valid) {
    expectt((new Email($invalid))->validate())->toBeFalse();
    expectt((new Email($valid))->validate())->toBeTrue();
})->with([
    ['gmai.com', 'example@gmail.com'],
    ['example.com', 'mira@gmail.com'],
]);

// it('Validate if the value is file extension', function ($invalid, $valid) {
//     expectt((new Ext($invalid))->validate())->toBeFalse();
//     expectt((new Ext($valid))->validate())->toBeTrue();
// })->with([
//     ['gmai.com', 'example@gmail.com'],
//     ['example.com', 'mira@gmail.com'],
// ])->skip('Why do you need to implement');

it('Validate if the value is in given list', function ($invalid, $valid) {
    expectt((new In($invalid))->setParameters([1, 2, 3])->validate())->toBeFalse();
    expectt((new In($valid))->setParameters([20, 30, 5])->validate())->toBeTrue();
})->with([
    [20, 20],
    [50, 30],
]);

it('Validate if the value is in given country list', function ($invalid, $valid) {
    expectt((new In($invalid))->setParameters(['USA', 'Peru', 'Mexico'])->validate())->toBeFalse();
    expectt((new In($valid))->setParameters(['China', 'Korea', 'Japon'])->validate())->toBeTrue();
})->with([
    ['Japon', 'Japon'],
    ['China', 'China'],
]);

it('Validate if the value is integer', function ($invalid, $valid) {
    expectt((new Integer($invalid))->validate())->toBeFalse();
    expectt((new Integer($valid))->validate())->toBeTrue();
})->with([
    ['gmai.com', 20],
    ['example.com', 50],
]);

it('Validate if the value is IP', function ($invalid, $valid) {
    expectt((new Ip($invalid))->validate())->toBeFalse();
    expectt((new Ip($valid))->validate())->toBeTrue();
})->with([
    ['127.78', '127.0.0.1'],
    ['255.09.45.78.IP', '127.255.20.1'],
]);

it('Validate if the value is Lowercase', function ($invalid, $valid) {
    expectt((new Lowercase($invalid))->validate())->toBeFalse();
    expectt((new Lowercase($valid))->validate())->toBeTrue();
})->with([
    ['UPPERCASE', 'lowercase'],
    ['PascalCase', 'snake_case'],
    [[], 'string'],
]);

// it('Validate if the value is max that', function ($invalid, $valid) {
//     expectt((new Max($invalid))->validate())->toBeFalse();
//     expectt((new Max($valid))->validate())->toBeTrue();
// })->with([
//     ['UPPERCASE', 'lowercase'],
//     ['PascalCase', 'snake_case'],
// ])->skip();

// it('Validate if the value is min that', function ($invalid, $valid) {
//     expectt((new Min($invalid))->validate())->toBeFalse();
//     expectt((new Min($valid))->validate())->toBeTrue();
// })->with([
//     ['UPPERCASE', 'lowercase'],
//     ['PascalCase', 'snake_case'],
// ])->skip();

it('Validate if the value is not in given list', function ($invalid, $valid) {
    expectt((new NotIn($invalid))->setParameters([1, 2, 3])->validate())->toBeFalse();
    expectt((new NotIn($valid))->setParameters([20, 30, 5])->validate())->toBeTrue();
})->with([
    [1, 55],
    [3, 40],
]);

it('Validate if the value is not in given country list', function ($invalid, $valid) {
    expectt((new NotIn($invalid))->setParameters(['USA', 'Peru', 'Mexico'])->validate())->toBeFalse();
    expectt((new NotIn($valid))->setParameters(['China', 'Korea', 'Japon'])->validate())->toBeTrue();
})->with([
    ['Peru', 'Mexico'],
    ['Mexico', 'Alemania'],
]);

it('Validate if the value is nullable, this return always true because all data is valid', function ($valid) {
    expectt((new Nullable($valid))->validate())->toBeTrue();
})->with([
    'Peru',
    'Mexico',
]);

it('Validate if the value is a number, an integer is not necessary', function ($invalid, $valid) {
    expectt((new Number($invalid))->validate())->toBeFalse();
    expectt((new Number($valid))->validate())->toBeTrue();
})->with([
    ['+25a', '55'],
    ['no-numeric', 40],
]);

it('Validate if the value is a valid regex', function ($invalid, $valid) {
    expectt((new Regex($invalid))->setParameters(['/[0-9]+/'])->validate())->toBeFalse();
    expectt((new Regex($valid))->setParameters(['/[0-9]+/'])->validate())->toBeTrue();
})->with([
    ['+a', '55'],
    ['no-numeric', '40'],
    [[], '40'],
]);

it('Validate if the value is a required field', function ($invalid, $valid) {
    expectt((new Required($invalid))->validate())->toBeFalse();
    expectt((new Required($valid))->validate())->toBeTrue();
})->with([
    ['', '55'],
    [[], ['hola']],
    [null, 'not-null'],
]);

it('Validate if the value is array', function ($invalid, $valid) {
    expectt((new RuleArray($invalid))->validate())->toBeFalse();
    expectt((new RuleArray($valid))->validate())->toBeTrue();
})->with([
    ['string', ['val', 'test']],
    [true, []],
]);

it('Validate if the value is object or associative array', function () {
    $validator = new RuleObject([]);

    expectt($validator->validate())->toBeTrue();

    expectt($validator->setValue(['val', 'test'])->validate())->toBeFalse();
    expectt($validator->setValue(['val' => 20, 'test' => 50])->validate())->toBeTrue();

    expectt($validator->setValue(['lower', 'mina'])->validate())->toBeFalse();
    expectt($validator->setValue(['val' => 20, 'test', 'test' => 'mikeal'])->validate())->toBeTrue();
});

// it('Validate if the value is same another attribute', function () {
//     $validator = new Same('');
// })->skip();

it('Validate if the value is UPPERCASE', function ($invalid, $valid) {
    expectt((new Uppercase($invalid))->validate())->toBeFalse();
    expectt((new Uppercase($valid))->validate())->toBeTrue();
})->with([
    [2345, 'UPPER'],
    ['Human', 'HUMAN'],
    [[], 'ARRAY'],
]);

it('Validate if the value is URL', function ($invalid, $valid) {
    expectt((new Url($invalid))->validate())->toBeFalse();
    expectt((new Url($valid))->validate())->toBeTrue();
})->with([
    [546, 'https://google.com'],
    ['Human', 'https://ping.com'],
    [[], 'http://migle.com?data=another'],
]);

it('Throw error if missing parameter', function () {
    $rule = (new In('26'))->setAttribute('in');

    $rule->validate();
})->throws(MissingRequiredParameterException::class);
