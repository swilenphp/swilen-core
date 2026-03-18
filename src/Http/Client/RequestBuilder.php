<?php

namespace Swilen\Http\Client;

use Psr\Http\Message\ResponseInterface;
use Swilen\Http\Common\HttpCase;

class RequestBuilder
{
    private Client $client;
    private string $method;
    private string $url;

    /**
     * The options for the request.
     *
     * @var array{headers: array<string, string>, query: array<string, mixed>, body: mixed, timeout: int}
     */
    private array $options = [
        'headers' => [],
        'query'   => [],
        'body'    => null,
        'timeout' => 30,
    ];

    public function __construct(Client &$client, string $method, string $url)
    {
        $this->client = $client;
        $this->method = HttpCase::uppercase($method);
        $this->url    = $url;
    }

    /**
     * Add a header to the request.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header(string $name, string $value): self
    {
        $this->options['headers'][$name] = $value;
        return $this;
    }

    /**
     * Add query parameters to the request.
     *
     * @param array<string, mixed> $params
     * @return $this
     */
    public function query(array $params): self
    {
        $this->options['query'] = array_merge($this->options['query'], $params);
        return $this;
    }

    /**
     * Add JSON data to the request.
     *
     * @param array<string, mixed> $data
     * @return $this
     */
    public function json(array $data): self
    {
        $this->options['headers']['Content-Type'] = 'application/json';
        $this->options['body'] = json_encode($data);
        return $this;
    }

    /**
     * Set the body of the request.
     *
     * @param mixed $body
     * @return $this
     */
    public function body($body): self
    {
        $this->options['body'] = $body;
        return $this;
    }

    /**
     * Set the timeout for the request.
     *
     * @param int $seconds
     * @return $this
     */
    public function timeout(int $seconds): self
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }

    /**
     * Send the request.
     *
     * @return ResponseInterface
     */
    public function send(): ResponseInterface
    {
        return $this->client->execute($this->method, $this->url, $this->options);
    }
}
