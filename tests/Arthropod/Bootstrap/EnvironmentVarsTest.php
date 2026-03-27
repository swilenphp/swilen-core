<?php

use Swilen\Arthropod\Application;
use Swilen\Arthropod\Bootstrap\EnvironmentVars;
use Swilen\Arthropod\Env;

uses()->group('Application');

afterEach(function () {
    Mockery::close();
    Env::forget();
});

it('Load environment file', function () {
    /**
     * @var \Mockery\MockInterface|\Swilen\Arthropod\Application $app
     */
    $app = Mockery::mock(Application::class);

    $app->shouldReceive('environmentPath')
        ->once()->with()->andReturn(__DIR__ . '/../__fixtures__');
    $app->shouldReceive('environmentFile')
        ->once()->with()->andReturn('.env.custom');

    (new EnvironmentVars())->bootstrap($app);

    expectt($_SERVER['FOO'])->toBe('FOO');
    expectt($_ENV['FOO'])->toBe('FOO');
    expectt($_SERVER['BAR'])->toBe('BAR');
    expectt($_ENV['BAR'])->toBe('BAR');

    test()->expectOutputString('');
});

it('Load custom environment instance', function () {
    /**
     * @var \Mockery\MockInterface|\Swilen\Arthropod\Application $app
     */
    $app = Mockery::mock(Application::class);

    $app->shouldReceive('environmentPath')
        ->times(0)->with()->andReturn(__DIR__ . '/../__fixtures__');
    $app->shouldReceive('environmentFile')
        ->times(0)->with()->andReturn('.env');

    EnvironmentVars::use(function () {
        return Env::createFrom(__DIR__ . '/../__fixtures__')->config([
            'file' => '.env.custom',
        ])->load();
    });

    (new EnvironmentVars())->bootstrap($app);

    expectt($_SERVER['CUSTOM'])->toBe('CUSTOM');
    expectt($_ENV['CUSTOM'])->toBe('CUSTOM');

    test()->expectOutputString('');
});

it('Load custom environment instance from factory', function () {
    EnvironmentVars::use(function () {
        return Env::createFrom(__DIR__ . '/../__fixtures__')->config([
            'file' => '.env.custom',
        ])->load();
    });

    expectt($_SERVER['CUSTOM'])->toBe('CUSTOM');
    expectt($_ENV['CUSTOM'])->toBe('CUSTOM');

    Env::forget();

    EnvironmentVars::use(function () {
        $hola = '';
    });
})->throws(TypeError::class, 'The callback expect a env object instance. Use env library, see https://github.com/vlucas/phpdotenv');
