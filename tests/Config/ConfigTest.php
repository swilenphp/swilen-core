<?php

use Swilen\Config\Contract\ConfigRepository;
use Swilen\Config\Repository;

uses()->group('Config');

it('Configuration repository', function () {
    $config = new Repository([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config)->toBeInstanceOf(ConfigRepository::class);

    expectt($config->get('nested.key'))->toBe(20);
    expectt($config->get('config.not', '__default'))->toBe('__default');
    expectt($config->has('nothing'))->toBeFalse();
    expectt($config->has('nested'))->toBeTrue();
});

it('Config manage values in runtime', function () {
    $config = new Repository([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config->all())->toHaveCount(1);

    $config->set('bar', 'fo');

    expectt($config->all())->toHaveCount(2);
    expectt($config->get('bar'))->toBe('fo');

    $config->push('nested', 25);

    expectt($config->all())->toHaveCount(2);
    expectt($config->get('nested'))->toBe(['key' => 20, 25]);
});

it('Config get many values from a key', function () {
    $config = new Repository([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config->getMany(['nested']))->toBe([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config->getMany('nested'))->toBe([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config->getMany('empty'))->toBe(['empty' => null]);
});

it('Interact with config instance as array', function () {
    $config = new Repository([
        'nested' => [
            'key' => 20,
        ],
    ]);

    expectt($config->all())->toHaveCount(1);

    $config['other.value'] = true;

    expectt($config->all())->toHaveCount(2);
    expectt($config['other.value'])->toBeTrue();

    expectt(isset($config['other']))->toBeTrue();
    expectt(isset($config['nothing']))->toBeFalse();

    unset($config['other']);

    expectt($config->all())->toHaveCount(2);
    expectt($config['other'])->toBeNull();
});
