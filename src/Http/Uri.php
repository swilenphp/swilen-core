<?php

namespace Swilen\Http;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface
{
    private string $scheme = '';
    private string $userInfo = '';
    private string $host = '';
    private ?int $port = null;
    private string $path = '';
    private string $query = '';
    private string $fragment = '';

    /**
     * Create a new URI instance.
     *
     * @param string $uri Complete URI string or empty string
     */
    public function __construct(string $uri = '')
    {
        if ($uri !== '') {
            $this->parseUri($uri);
        }
    }

    /**
     * Parse a URI string into its components.
     */
    private function parseUri(string $uri): void
    {
        $parts = parse_url($uri);

        if ($parts === false) {
            throw new \InvalidArgumentException("Unable to parse URI: {$uri}");
        }

        $this->scheme = isset($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $this->host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $this->port = $parts['port'] ?? null;
        $this->path = $parts['path'] ?? '';
        $this->query = $parts['query'] ?? '';
        $this->fragment = $parts['fragment'] ?? '';

        if (isset($parts['user'])) {
            $this->userInfo = $parts['user'];
            if (isset($parts['pass'])) {
                $this->userInfo .= ':' . $parts['pass'];
            }
        }
    }

    /**
     * Return the string representation as a URI reference.
     */
    public function __toString(): string
    {
        $uri = '';

        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }

        if ($this->host !== '') {
            $uri .= '//' . $this->userInfo;
            if ($this->userInfo !== '') {
                $uri .= '@';
            }
            $uri .= $this->host;

            if ($this->port !== null) {
                $uri .= ':' . $this->port;
            }
        }

        $uri .= $this->path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    /**
     * Retrieve the scheme component of the URI.
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Retrieve the authority component of the URI.
     */
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }

        $authority = $this->host;

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * Retrieve the user information component of the URI.
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * Retrieve the host component of the URI.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Retrieve the port component of the URI.
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * Retrieve the path component of the URI.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieve the query string of the URI.
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Retrieve the fragment component of the URI.
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Return an instance with the specified scheme.
     */
    public function withScheme($scheme): self
    {
        if (!is_string($scheme)) {
            throw new \InvalidArgumentException('Scheme must be a string');
        }

        $scheme = strtolower($scheme);

        if ($this->scheme === $scheme) {
            return $this;
        }

        $clone = clone $this;
        $clone->scheme = $scheme;

        return $clone;
    }

    /**
     * Return an instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): self
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException('User must be a string');
        }

        $userInfo = $user;

        if ($password !== null) {
            if (!is_string($password)) {
                throw new \InvalidArgumentException('Password must be a string');
            }
            $userInfo .= ':' . $password;
        }

        if ($this->userInfo === $userInfo) {
            return $this;
        }

        $clone = clone $this;
        $clone->userInfo = $userInfo;

        return $clone;
    }

    /**
     * Return an instance with the specified host.
     */
    public function withHost($host): self
    {
        if (!is_string($host)) {
            throw new \InvalidArgumentException('Host must be a string');
        }

        $host = strtolower($host);

        if ($this->host === $host) {
            return $this;
        }

        $clone = clone $this;
        $clone->host = $host;

        return $clone;
    }

    /**
     * Return an instance with the specified port.
     */
    public function withPort($port): self
    {
        if ($port !== null) {
            if (!is_int($port) || $port < 0 || $port > 65535) {
                throw new \InvalidArgumentException('Port must be between 0 and 65535');
            }
        }

        if ($this->port === $port) {
            return $this;
        }

        $clone = clone $this;
        $clone->port = $port;

        return $clone;
    }

    /**
     * Return an instance with the specified path.
     */
    public function withPath($path): self
    {
        if (!is_string($path)) {
            throw new \InvalidArgumentException('Path must be a string');
        }

        if ($this->path === $path) {
            return $this;
        }

        $clone = clone $this;
        $clone->path = $path;

        return $clone;
    }

    /**
     * Return an instance with the specified query string.
     */
    public function withQuery($query): self
    {
        if (!is_string($query)) {
            throw new \InvalidArgumentException('Query must be a string');
        }

        if ($this->query === $query) {
            return $this;
        }

        $clone = clone $this;
        $clone->query = $query;

        return $clone;
    }

    /**
     * Return an instance with the specified URI fragment.
     */
    public function withFragment($fragment): self
    {
        if (!is_string($fragment)) {
            throw new \InvalidArgumentException('Fragment must be a string');
        }

        if ($this->fragment === $fragment) {
            return $this;
        }

        $clone = clone $this;
        $clone->fragment = $fragment;

        return $clone;
    }

    /**
     * Create a Uri from server variables.
     */
    public static function fromServerRequest(array $server): self
    {
        $scheme = 'http';
        if (isset($server['HTTPS']) && ($server['HTTPS'] === 'on' || $server['HTTPS'] === '1')) {
            $scheme = 'https';
        }

        $host = $server['SERVER_NAME'] ?? $server['HTTP_HOST'] ?? 'localhost';
        $port = isset($server['SERVER_PORT']) ? (int)$server['SERVER_PORT'] : null;

        // Remove default ports from URI
        if (
            ($scheme === 'https' && $port === 443) ||
            ($scheme === 'http' && $port === 80)
        ) {
            $port = null;
        }

        $path = $server['REQUEST_URI'] ?? '/';
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        $query = $server['QUERY_STRING'] ?? '';
        $userInfo = '';

        if (isset($server['PHP_AUTH_USER'])) {
            $userInfo = $server['PHP_AUTH_USER'];
            if (isset($server['PHP_AUTH_PW'])) {
                $userInfo .= ':' . $server['PHP_AUTH_PW'];
            }
        }

        $uri = new self();
        $uri->scheme = $scheme;
        $uri->host = $host;
        $uri->port = $port;
        $uri->path = $path;
        $uri->query = $query;
        $uri->userInfo = $userInfo;

        return $uri;
    }
}
