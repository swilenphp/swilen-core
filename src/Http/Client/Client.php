<?php

namespace Swilen\Http\Client;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Client
{
    protected $config;

    protected readonly Interceptors $interceptors;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'base_uri' => '',
            'headers'  => [],
            'timeout'  => 30,
        ], $config);

        $this->interceptors = new Interceptors();
    }

    public static function builder(): self
    {
        return new self();
    }

    public function get(string $url): RequestBuilder
    {
        return new RequestBuilder($this, 'GET', $url);
    }
    public function post(string $url): RequestBuilder
    {
        return new RequestBuilder($this, 'POST', $url);
    }
    public function put(string $url): RequestBuilder
    {
        return new RequestBuilder($this, 'PUT', $url);
    }
    public function delete(string $url): RequestBuilder
    {
        return new RequestBuilder($this, 'DELETE', $url);
    }

    /**
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->execute($request->getMethod(), (string)$request->getUri(), [
            'headers' => $request->getHeaders(),
            'body'    => (string)$request->getBody()
        ]);
    }

    /**
     */
    public function execute(string $method, string $url, array $options): ResponseInterface
    {
        $fullUrl = $this->buildUrl($url, $options['query'] ?? []);
        $headers = array_merge($this->config['headers'], $options['headers'] ?? []);

        $swoole = '\Swoole\Coroutine';
        if (extension_loaded('swoole') && $swoole::getCid() > 0) {
            return $this->sendSwoole($method, $fullUrl, $headers, $options);
        }

        return $this->sendCurl($method, $fullUrl, $headers, $options);
    }

    private function buildUrl($url, $query)
    {
        $url = trim($this->config['base_uri'], '/') . '/' . ltrim($url, '/');
        if (!empty($query)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
        }
        return $url;
    }

    private function sendCurl($method, $url, $headers, $options): ResponseInterface
    {
        $ch = curl_init();
        if (isset($options['query'])) {
            $url .= '?' . http_build_query($options['query']);
        }

        $curlHeaders = [];
        foreach ($headers as $k => $v) $curlHeaders[] = "$k: $v";

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => $curlHeaders,
            CURLOPT_TIMEOUT => $options['timeout'] ?? $this->config['timeout'],
            CURLOPT_POSTFIELDS => $options['body'] ?? null,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        $info = curl_getinfo($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        if (function_exists('curl_close')) {
            curl_close($ch);
        }

        $resHeaders = substr($response, 0, $headerSize);
        $resBody = substr($response, $headerSize);

        return new Response($info['http_code'], $this->parseHeaders($resHeaders), $resBody);
    }

    private function sendSwoole($method, $url, $headers, $options): ResponseInterface
    {
        $parsed = parse_url($url);
        $host = $parsed['host'];
        $port = $parsed['port'] ?? ($parsed['scheme'] === 'https' ? 443 : 80);
        $ssl = $parsed['scheme'] === 'https';

        // @phpstan-ignore-next-line
        $class = "\Swoole\Coroutine\Http\Client";

        $client = new $class($host, $port, $ssl);
        $client->setMethod($method);
        $client->setHeaders($headers);
        $client->set(['timeout' => $options['timeout']]);

        if ($options['body']) $client->setData($options['body']);

        $path = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? '?' . $parsed['query'] : '');
        $client->execute($path);

        $res = new Response($client->statusCode, $client->headers ?? [], $client->body);
        $client->close();

        return $res;
    }

    private function parseHeaders($raw)
    {
        $headers = [];
        foreach (explode("\r\n", $raw) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }
}
