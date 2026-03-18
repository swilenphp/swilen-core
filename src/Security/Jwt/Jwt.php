<?php

namespace Swilen\Security\Jwt;

use Swilen\Security\Contract\JwtService;
use Swilen\Security\Exception\JwtDomainException;
use Swilen\Security\Exception\JwtFatalException;
use Swilen\Security\Exception\JwtInvalidSignatureException;
use Swilen\Security\Exception\JwtTokenExpiredException;

final class Jwt implements JwtService
{
    /**
     * The token header.
     *
     * @var \Swilen\Security\Jwt\Header
     */
    private $header;

    /**
     * The shared secret key.
     *
     * @var string
     */
    private $secretKey;

    /**
     * The shared singed options.
     *
     * @var array<string,mixed>
     */
    private $signOptions = [];

    /**
     * The current algorithm for hashing.
     *
     * @var string
     */
    private $algorithm = 'HS256';

    /**
     * Collection of supported algorithms.
     *
     * @var array<string,string[]>
     */
    public const SUPPORTED_ALGORITHMS = [
        // HMAC
        'HS256' => ['hash_hmac', 'sha256'],
        'HS384' => ['hash_hmac', 'sha384'],
        'HS512' => ['hash_hmac', 'sha512'],

        // RSA
        'RS256' => ['openssl', OPENSSL_ALGO_SHA256],
        'RS384' => ['openssl', OPENSSL_ALGO_SHA384],
        'RS512' => ['openssl', OPENSSL_ALGO_SHA512],

        // EdDSA
        'EdDSA' => ['sodium', 'ed25519'],
    ];

    /**
     * Indicated if token manager previusly confired with default values.
     *
     * @var bool
     */
    private $configured = false;

    /**
     * Configure initial values to Jwt Manager.
     *
     * @param string $secret     The secret key
     * @param array  $ignOptions Initial ign options
     *
     * ```php
     * <?php
     *  $signOptions = ['expires' => '60s', 'algorithm' => 'HS512'];
     * ````
     *
     * @return static
     */
    public static function register(string $secret, array $signOptions = [])
    {
        return (new static())->useConfig($secret, $signOptions);
    }

    /**
     * Configure initial values to Jwt Manager.
     *
     * @param string $secret     The secret key
     * @param array  $ignOptions Initial ign options
     *
     * @return $this
     */
    private function useConfig(string $secret, array $signOptions)
    {
        $this->secretKey   = $secret;
        $this->signOptions = OptionsValidator::validate($signOptions);
        $this->configured  = true;

        return $this;
    }

    /**
     * Sign new token with claims in payload {@inheritdoc}
     *
     * @param key-of<\Swilen\Security\Jwt\Jwt::SUPPORTED_ALGORITHMS> $algo
     *
     * @return \Swilen\Security\Jwt\JwtSignedExpression
     */
    public function sign(array $payload, $secret = null, $algo = null)
    {
        $this->ensureIfPreviouslyConfigured($secret, $algo);

        $payload = $this->makePayload($payload);

        $headersEncoded = $this->header->encode();
        $payloadEncoded = $payload->encode();

        $signature = $this->signMessage(Util::dotted($headersEncoded, $payloadEncoded));

        return new JwtSignedExpression(Util::dotted(
            $headersEncoded,
            $payloadEncoded,
            $signature,
        ), $payload);
    }

    /**
     * Manage token verification {@inheritdoc}
     *
     * @param key-of<\Swilen\Security\Jwt\Jwt::SUPPORTED_ALGORITHMS> $algo
     *
     * @throws \Swilen\Security\Exception\JwtTokenExpiredException
     * @throws \Swilen\Security\Exception\JwtInvalidSignatureException
     */
    public function verify(string $token, $secret = null, ?string $algo = null)
    {
        $this->ensureIfPreviouslyConfigured($secret, $algo);

        $decoded = (new Decoder($token))->decode();

        $alg = $decoded->header->alg ?? null;

        if (!isset(self::SUPPORTED_ALGORITHMS[$alg])) {
            throw new JwtDomainException('The algorithm "' . $alg . '" is not supported.');
        }

        $this->algorithm = $alg;

        $message = Util::dotted(
            $decoded->header->encode(),
            $decoded->payload->encode()
        );

        return $this->validateWithErrorHandling($decoded, $message);
    }

    /**
     * Create new hash_mac signature.
     *
     * @param string $message Message for hashing
     * @param string $secret
     *
     * @return string
     */
    private function signMessage(string $message)
    {
        [$type, $algorithm] = self::SUPPORTED_ALGORITHMS[$this->algorithm];

        switch ($type) {
            case 'hash_hmac':
                $this->ensureFunctionExists('hash_hmac');
                return Util::url_encode(
                    hash_hmac($algorithm, $message, $this->secretKey, true)
                );

            case 'openssl':
                $this->ensureFunctionExists('openssl_sign');
                openssl_sign($message, $signature, $this->secretKey, $algorithm);
                return Util::url_encode($signature);

            case 'sodium':
                $this->ensureFunctionExists('sodium_crypto_sign_detached');
                return Util::url_encode(
                    sodium_crypto_sign_detached($message, $this->secretKey)
                );

            default:
                throw new JwtDomainException('Unsupported signing method.');
        }
    }

    /**
     * Validate rules for incoming decoded token.
     *
     * @param \Swilen\Security\Jwt\Decoder $decoded
     * @param string                         $message
     *
     * @return \Swilen\Security\Jwt\Payload
     *
     * @throws \Swilen\Security\Exception\JwtTokenExpiredException
     * @throws \Swilen\Security\Exception\JwtInvalidSignatureException
     */
    private function validateWithErrorHandling(Decoder $decoded, string $message)
    {
        if ($decoded->payload->expires() !== null && $decoded->payload->expires() < time()) {
            throw new JwtTokenExpiredException();
        }

        if (!$this->isValidSignature($message, $decoded->signature)) {
            throw new JwtInvalidSignatureException();
        }

        return $decoded->payload;
    }

    /**
     * Verify if hash signature is valid.
     *
     * @param string $left
     * @param string $right
     *
     * @return bool
     */
    private function isValidSignature(string $message, string $signature)
    {
        [$type, $algorithm] = self::SUPPORTED_ALGORITHMS[$this->algorithm];

        $signature = Util::url_decode($signature);

        switch ($type) {
            case 'hash_hmac':
                $this->ensureFunctionExists('hash_hmac');
                $expected = hash_hmac($algorithm, $message, $this->secretKey, true);
                return Util::hash_equals($expected, $signature);

            case 'openssl':
                $this->ensureFunctionExists('openssl_verify');
                return openssl_verify($message, $signature, $this->secretKey, $algorithm) === 1;

            case 'sodium':
                $this->ensureFunctionExists('sodium_crypto_sign_verify_detached');
                return sodium_crypto_sign_verify_detached(
                    $signature,
                    $message,
                    $this->secretKey
                );

            default:
                return false;
        }
    }

    /**
     * Create new jwt header with algorithm.
     *
     * @param string|null $algo
     *
     * @return void
     *
     * @throws \Swilen\Security\Exception\JwtDomainException
     */
    private function makeHeaderWithAlgorithm($algo)
    {
        if (!isset(self::SUPPORTED_ALGORITHMS[$algo])) {
            throw new JwtDomainException(sprintf('The algorithm "%s" is not supported.', $algo), 500);
        }

        $this->algorithm = $algo;

        $this->header = new Header(['alg' => $algo, 'typ' => 'JWT']);
    }

    /**
     * Verify if payload contains valid options and create Payload instance.
     *
     * @param array $payload
     *
     * @return \Swilen\Security\Jwt\Payload
     */
    private function makePayload(array $payload)
    {
        if ($this->hasPreviouslyConfigured()) {
            if (!isset($payload['data'])) {
                $payload['data'] = $payload;
            }

            $payload['exp'] = time() + $this->signOptions['expires'];
        }

        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }

        // Add default expiration time (60 seconds) if not passed
        if (!isset($payload['exp'])) {
            $payload['exp'] = time() + 60;
        }

        return new Payload($payload);
    }

    /**
     * Handle if token manager previusly configured.
     *
     * @param string $secret
     * @param string $algo
     *
     * @return void
     */
    private function ensureIfPreviouslyConfigured($secret, $algo)
    {
        // Manage if not configured
        if (!$this->hasPreviouslyConfigured()) {
            if (!$secret) {
                throw new JwtDomainException('Missing secret key.');
            }

            $this->secretKey = $secret;

            if (!$algo) {
                throw new JwtDomainException('Missing algorithm.');
            }

            return $this->makeHeaderWithAlgorithm($algo);
        }

        if ($this->header === null && $this->hasPreviouslyConfigured()) {
            if (!$algorithm = $this->signOptions['algorithm'] ?? $algo) {
                throw new JwtDomainException('Missing algorithm.');
            }

            $this->makeHeaderWithAlgorithm($algorithm);
        }
    }

    /**
     * Veify token manager if previusly configured.
     *
     * @return bool
     */
    private function hasPreviouslyConfigured()
    {
        return $this->configured === true;
    }

    private function ensureFunctionExists(string $function)
    {
        if (!function_exists($function)) {
            throw new JwtFatalException(sprintf('The function "%s" is not exists.', $function), 500);
        }
    }
}
