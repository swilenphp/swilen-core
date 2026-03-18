<?php

use Swilen\Container\Container;
use Swilen\Petiole\Facade;
use Swilen\Security\Contract\JwtService;
use Swilen\Security\Exception\JwtDomainException;
use Swilen\Security\Jwt\Jwt;
use Swilen\Security\Jwt\JwtSignedExpression;
use Swilen\Security\Jwt\Payload;

uses()->group('Security', 'Token');

define('APP_MANAGER_SECRET', 'jwt6350d205f2b4385ngfuftg');

beforeAll(function () {
    $container = Container::getInstance();

    $container->singleton(JwtService::class, function ($app) {
        return Jwt::register(APP_MANAGER_SECRET, [
            'expires' => '60s',
            'algorithm' => 'HS512',
        ]);
    });

    Facade::setFacadeApplication($container);
});

beforeEach(function () {
    $this->container = Container::getInstance();
});

afterAll(function () {
    Container::getInstance()->flush();
    Facade::flushFacadeInstances();
});

it('Create new token instance from token manager as singleton', function () {
    $tokenManager = $this->container[JwtService::class];

    expectt($tokenManager)->toBeInstanceOf(Jwt::class);
    expectt($tokenManager)->toBeInstanceOf(JwtService::class);
});

it('Create token signature from token manager', function () {
    /** @var JwtService */
    $manager = $this->container[JwtService::class];

    $token = $manager->sign([
        'userId' => uniqid(),
        'username' => 'bar',
        'role' => 'admin',
    ]);

    expectt($token)->toBeInstanceOf(JwtSignedExpression::class);
    expectt($token->plainTextToken)->toBeString();
    expectt($token->payload->expires())->toBeNumeric();

    $decoded = $manager->verify($token->__toString());

    expectt($decoded)->toBeInstanceOf(Payload::class);
    expectt($decoded->data())->toBeArray();
    expectt($decoded->expires())->toBeNumeric();
});

it('Facade is correct resolved', function () {
    $token = JwtToken::sign([
        'userId' => uniqid(),
        'username' => 'bar',
        'role' => 'admin',
    ]);

    expectt($token)->toBeInstanceOf(JwtSignedExpression::class);
    expectt($token->plainTextToken)->toBeString();
    expectt($token->payload->expires())->toBeNumeric();

    $decoded = JwtToken::verify($token->__toString());

    expectt($decoded)->toBeInstanceOf(Payload::class);
    expectt($decoded->data())->toBeArray()->toHaveKeys(['userId', 'username', 'role']);
    expectt($decoded->expires())->toBeNumeric();
});

it('Throw error when alrgithm not is provide', function () {
    $manager = Jwt::register(APP_MANAGER_SECRET, [
        'expires' => '160s',
    ]);

    $manager->sign([
        'data' => '',
    ]);
})->throws(JwtDomainException::class, 'Missing algorithm.');

/**
 * @method static \Swilen\Security\Jwt\JwtSignedExpression sign(array $payload, string $secret = null, string $algo = 'HS256')
 * @method static \Swilen\Security\Jwt\Payload             verify(string $token, $secret = null, ?string $algo = null)
 */
class JwtToken extends Facade
{
    protected static function getFacadeName()
    {
        return JwtService::class;
    }
}
