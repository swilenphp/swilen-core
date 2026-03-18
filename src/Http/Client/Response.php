<?php

namespace Swilen\Http\Client;

use Psr\Http\Message\ResponseInterface;

class Response implements ResponseInterface
{
    private $statusCode;
    private $headers;
    private $body;
    private $reasonPhrase;

    public function __construct($status, $headers, $body, $reason = '')
    {
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->body = $body;
        $this->reasonPhrase = $reason;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
    public function getHeaders(): array
    {
        return $this->headers;
    }
    public function getBody()
    {
        return $this->body;
    }
    // Implementación mínima de otros métodos PSR-7...
    public function getProtocolVersion()
    {
        return '1.1';
    }
    public function withProtocolVersion($version)
    {
        return $this;
    }
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }
    public function getHeader($name)
    {
        return (array)($this->headers[$name] ?? []);
    }
    public function getHeaderLine($name)
    {
        return $this->headers[$name] ?? '';
    }
    public function withHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }
    public function withAddedHeader($name, $value)
    {
        return $this;
    }
    public function withoutHeader($name)
    {
        unset($this->headers[$name]);
        return $this;
    }
    public function withStatus($code, $reasonPhrase = '')
    {
        $this->statusCode = $code;
        return $this;
    }
    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }
    public function withBody(\Psr\Http\Message\StreamInterface $body)
    {
        return $this;
    }
}
