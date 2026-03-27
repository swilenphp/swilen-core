<?php

/*
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swilen\Http\Component;

class CookieJar
{
    /**
     * The cookies store
     *
     * @var array<string,array<string,array<string,Cookie>>>
     */
    protected array $store = [];

    public function __construct(array $setCookies = [], ?string $uri = null)
    {
        $this->updateFromSetCookie($setCookies, $uri);
    }

    /**
     * Sets a cookie.
     */
    public function set(Cookie $cookie): void
    {
        $this->store[$cookie->getDomain()][$cookie->getPath()][$cookie->getName()] = $cookie;
    }

    /**
     * Gets a cookie by name.
     *
     * You should never use an empty domain, but if you do so,
     * this method returns the first cookie for the given name/path
     * (this behavior ensures a BC behavior with previous versions of
     * Symfony).
     */
    public function get(string $name, string $path = '/', ?string $domain = null): ?Cookie
    {
        $this->flushExpiredCookies();

        foreach ($this->store as $cookieDomain => $pathCookies) {
            if ($cookieDomain && $domain) {
                $cookieDomain = '.' . ltrim($cookieDomain, '.');
                if (!str_ends_with('.' . $domain, $cookieDomain)) {
                    continue;
                }
            }

            foreach ($pathCookies as $cookiePath => $namedCookies) {
                if (!str_starts_with($path, $cookiePath)) {
                    continue;
                }
                if (isset($namedCookies[$name])) {
                    return $namedCookies[$name];
                }
            }
        }

        return null;
    }

    /**
     * Removes a cookie by name.
     *
     * You should never use an empty domain, but if you do so,
     * all cookies for the given name/path expire (this behavior
     * ensures a BC behavior with previous versions of Symfony).
     */
    public function expire(string $name, ?string $path = '/', ?string $domain = null): void
    {
        $path ??= '/';

        if (!$domain) {
            // an empty domain means any domain
            // this should never happen but it allows for a better BC
            $domains = array_keys($this->store);
        } else {
            $domains = [$domain];
        }

        foreach ($domains as $domain) {
            unset($this->store[$domain][$path][$name]);

            if (empty($this->store[$domain][$path])) {
                unset($this->store[$domain][$path]);

                if (empty($this->store[$domain])) {
                    unset($this->store[$domain]);
                }
            }
        }
    }

    /**
     * Removes all the cookies from the jar.
     */
    public function clear(): void
    {
        $this->store = [];
    }

    /**
     * Updates the cookie jar from a response Set-Cookie headers.
     *
     * @param string[] $setCookies Set-Cookie headers from an HTTP response
     */
    public function updateFromSetCookie(array $setCookies, ?string $uri = null): void
    {
        $cookies = [];

        foreach ($setCookies as $cookie) {
            foreach (\explode(',', $cookie) as $i => $part) {
                if (0 === $i || \preg_match('/^(?P<token>\s*[0-9A-Za-z!#\$%\&\'\*\+\-\.^_`\|~]+)=/', $part)) {
                    $cookies[] = \ltrim($part);
                } else {
                    $cookies[\count($cookies) - 1] .= ',' . $part;
                }
            }
        }

        foreach ($cookies as $cookie) {
            try {
                $this->set(Cookie::fromString($cookie, $uri));
            } catch (\InvalidArgumentException) {
                // invalid cookies are just ignored
            }
        }
    }

    /**
     * Returns not yet expired cookies.
     *
     * @return Cookie[]
     */
    public function all(): array
    {
        $this->flushExpiredCookies();

        $flattened = [];
        foreach ($this->store as $path) {
            foreach ($path as $cookies) {
                foreach ($cookies as $cookie) {
                    $flattened[] = $cookie;
                }
            }
        }

        return $flattened;
    }

    /**
     * Returns not yet expired cookie values for the given URI.
     */
    public function allValues(string $uri, bool $returnsRawValue = false): array
    {
        $this->flushExpiredCookies();

        $parts = \array_replace(['path' => '/'], \parse_url($uri));
        $cookies = [];
        foreach ($this->store as $domain => $pathCookies) {
            if ($domain) {
                $domain = '.' . \ltrim($domain, '.');
                if (!\str_ends_with('.' . $parts['host'], $domain)) {
                    continue;
                }
            }

            foreach ($pathCookies as $path => $namedCookies) {
                if (!\str_starts_with($parts['path'], $path)) {
                    continue;
                }

                foreach ($namedCookies as $cookie) {
                    if ($cookie->isSecure() && 'https' !== $parts['scheme']) {
                        continue;
                    }

                    $cookies[$cookie->getName()] = $returnsRawValue ? $cookie->getRawValue() : $cookie->getValue();
                }
            }
        }

        return $cookies;
    }

    /**
     * Returns not yet expired raw cookie values for the given URI.
     */
    public function allRawValues(string $uri): array
    {
        return $this->allValues($uri, true);
    }

    /**
     * Removes all expired cookies.
     */
    public function flushExpiredCookies(): void
    {
        foreach ($this->store as $domain => $pathCookies) {
            foreach ($pathCookies as $path => $namedCookies) {
                foreach ($namedCookies as $name => $cookie) {
                    if ($cookie->isExpired()) {
                        unset($this->store[$domain][$path][$name]);
                    }
                }
            }
        }
    }
}
