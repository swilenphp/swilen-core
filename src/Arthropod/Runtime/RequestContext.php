<?php

namespace Swilen\Arthropod\Runtime;

use OpenSwoole\Coroutine;
use Swilen\Http\Request;
use Throwable;

/**
 * RequestContext
 *
 * Isolates PHP superglobals ($_GET, $_POST, $_COOKIE, $_SERVER, $_FILES,
 * $_REQUEST, $_SESSION, $argv, $argc) per coroutine, using Swoole's native
 * coroutine context (Coroutine::getContext()).
 *
 * Basic usage:
 *```
 *   $server->on('request', function ($req, $resp) {
 *       $request = RequestContext::capture($req);
 *       try {
 *           // Here $_GET, $_POST, etc. are loaded correctly.
 *           $name = $_GET['name'] ?? 'world';
 *           $resp->end("Hello $name");
 *       } finally {
 *           RequestContext::free();
 *       }
 *   });
 *
 * ```
 * Extension with events:
 *```
 *   RequestContext::on('capture', function (array &$ctx, $request) {
 *       $ctx['user'] = resolve_user($request);
 *   });
 *
 *   RequestContext::on('free', function (array &$ctx) {
 *       // custom cleanup
 *   });
 *```
 * Direct access to the current coroutine context:
 *
 *```
 *   $user = RequestContext::get('user');
 *   RequestContext::set('foo', 'bar');
 * ```
 */
final class RequestContext
{
    /**
     * Root key inside Coroutine::getContext() where all request data is stored.
     */
    private const CTX_KEY = '__request_ctx__';

    /**
     * Superglobals that are saved and restored.
     * Order matters: they are captured in this order and restored in reverse.
     *
     * @var string[]
     */
    private const SUPERGLOBALS = [
        '_GET',
        '_POST',
        '_COOKIE',
        '_FILES',
        '_SERVER',
        '_REQUEST',
        '_SESSION',
    ];

    /**
     * Registered handlers per event.
     *
     * Supported events:
     *   'capture' - fired at the end of capture(), receives (&$ctx, $request, &$psr7)
     *   'free'    - fired at the beginning of free(), receives (&$ctx)
     *
     * @var array<string, callable[]>
     */
    private static array $listeners = [
        'capture' => [],
        'free' => [],
    ];

    /**
     * Cached flag indicating whether Swoole/OpenSwoole is available.
     */
    private static ?bool $swooleAvailable = null;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Registers a listener for a lifecycle event.
     *
     * @param string   $event   'capture' | 'free'
     * @param callable $handler
     *
     * @throws \InvalidArgumentException If the event does not exist.
     */
    public static function on(string $event, callable $handler): void
    {
        if (!\key_exists($event, self::$listeners)) {
            throw new \InvalidArgumentException(
                "Unknown event '$event'. Valid events: " .
                \implode(', ', \array_keys(self::$listeners))
            );
        }

        self::$listeners[$event][] = $handler;
    }

    /**
     * Removes all listeners from one event (useful in tests).
     *
     * @param string|null $event Null to clear all events.
     */
    public static function removeListeners(?string $event = null): void
    {
        if ($event === null) {
            foreach (self::$listeners as $key => $_) {
                self::$listeners[$key] = [];
            }
            return;
        }

        if (\key_exists($event, self::$listeners)) {
            self::$listeners[$event] = [];
        }
    }

    /**
     * Captures the current Swoole request state and assigns it to PHP superglobals
     * in the current coroutine context.
     *
     * It must be called at the beginning of each request handler.
     *
     * @param object|null $request Swoole\Http\Request object (or compatible).
     *                              If null, the current process superglobals are used
     *                              instead (useful in CLI/tests).
     * @param object|null $psr7    Optional output parameter. If provided and a PSR-7
     *                              request can be built, it will receive the PSR-7
     *                              request object. If null, nothing is assigned.
     *
     * @throws \RuntimeException If called outside a Swoole coroutine.
     */
    public static function capture(?object $request = null): Request
    {
        self::assertCoroutine();

        $coroutineCtx = Coroutine::getContext();

        if (isset($coroutineCtx[self::CTX_KEY])) {
            trigger_error(
                'RequestContext::capture() called twice in the same coroutine ' .
                '(cid=' . Coroutine::getCid() . '). The previous context will be replaced.',
                E_USER_WARNING
            );
        }

        $backup = self::snapshotGlobals();
        $state = self::buildStateFromRequest($request);

        $coroutineCtx[self::CTX_KEY] = [
            'state' => $state,
            'backup' => $backup,
        ];

        self::applyStateToPHPGlobals($state);
        self::dispatch('capture', $state, $request);

        return Request::captureFromContext($state['_SERVER'], $request?->header ?? [], $state['_FILES'] ?? [], $state['_POST'] ?? [], $state['_GET'] ?? [], $request?->getContent() ?? null);
    }

    /**
     * Releases the current coroutine context and restores the previous PHP superglobals.
     *
     * It should always be called inside a finally block.
     *
     * @param bool $silent If true, it does not throw if no context exists
     *                      (useful when free() is called defensively).
     */
    public static function free(bool $silent = false): void
    {
        if (!self::isSwooleAvailable()) {
            return;
        }

        $coroutineCtx = Coroutine::getContext();

        if (!isset($coroutineCtx[self::CTX_KEY])) {
            if ($silent) {
                return;
            }

            throw new \RuntimeException(
                'RequestContext::free() called without a previous context ' .
                '(cid=' . Coroutine::getCid() . '). ' .
                'Did you forget to call RequestContext::capture()?'
            );
        }

        $entry = $coroutineCtx[self::CTX_KEY];

        self::dispatch('free', $entry['state'], null);
        self::restoreGlobals($entry['backup']);
        unset($coroutineCtx[self::CTX_KEY]);
    }

    /**
     * Gets a value from the current coroutine context.
     *
     * @param string $key     Key to retrieve.
     * @param mixed  $default Default value if the key does not exist.
     *
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $entry = self::currentEntry();
        return $entry['state'][$key] ?? $default;
    }

    /**
     * Sets a value in the current coroutine context.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function set(string $key, mixed $value): void
    {
        self::assertContext();
        $coroutineCtx = Coroutine::getContext();
        $coroutineCtx[self::CTX_KEY]['state'][$key] = $value;
    }

    /**
     * Checks whether a value exists in the current coroutine context.
     */
    public static function has(string $key): bool
    {
        $entry = self::currentEntry();
        return isset($entry['state'][$key]);
    }

    /**
     * Returns a copy of the full current context state.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::currentEntry()['state'] ?? [];
    }

    /**
     * Returns the current coroutine ID, useful for logging/debugging.
     */
    public static function currentCoroutineId(): int
    {
        return self::isSwooleAvailable() ? Coroutine::getCid() : -1;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Builds the state array from a Swoole request object.
     *
     * @param \OpenSwoole\Http\Request|null $request Swoole\Http\Request object (or compatible).
     *
     * @return array<string, mixed>
     */
    private static function buildStateFromRequest(?object $request): array
    {
        if ($request === null) {
            $state = [];
            foreach (self::SUPERGLOBALS as $name) {
                $state[$name] = $GLOBALS[$name] ?? [];
            }
            $state['argv'] = $GLOBALS['argv'] ?? [];
            $state['argc'] = $GLOBALS['argc'] ?? 0;
            return $state;
        }

        $get = (array) ($request->get ?? []);
        $post = (array) ($request->post ?? []);
        $cookie = (array) ($request->cookie ?? []);
        $files = self::normalizeFiles((array) ($request->files ?? []));
        $server = self::buildServerFromRequest($request);

        $requestArr = [...$cookie, ...$get, ...$post];

        return [
            '_GET' => $get,
            '_POST' => $post,
            '_COOKIE' => $cookie,
            '_FILES' => $files,
            '_SERVER' => $server,
            '_REQUEST' => $requestArr,
            '_SESSION' => [],
            'argv' => [],
            'argc' => 0,
        ];
    }

    /**
     * Builds $_SERVER from a Swoole request.
     *
     * @return array<string, mixed>
     */
    private static function buildServerFromRequest(object $request): array
    {
        $server = (array) ($request->server ?? []);
        $header = (array) ($request->header ?? []);

        foreach ($header as $key => $value) {
            $normalizedKey = 'HTTP_' . \strtoupper(str_replace('-', '_', $key));
            $server[$normalizedKey] = $value;
        }

        $server['REQUEST_METHOD'] = $request->getMethod() ?? 'GET';
        $server['REQUEST_URI'] = $server['request_uri'] ?? '/';
        $server['SERVER_PROTOCOL'] = $server['server_protocol'] ?? 'HTTP/1.1';
        $server['CONTENT_TYPE'] = $header['content-type'] ?? '';
        $server['CONTENT_LENGTH'] = $header['content-length'] ?? '';
        $server['REMOTE_ADDR'] = $server['remote_addr'] ?? '';
        $server['SERVER_NAME'] = $header['host'] ?? ($server['server_name'] ?? '');

        return \array_map('strval', $server);
    }

    /**
     * Normalizes $_FILES to match standard PHP structure.
     *
     * @param array<string, mixed> $files
     * @return array<string, mixed>
     */
    private static function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $field => $file) {
            if (\is_array($file) && isset($file['name'])) {
                $normalized[$field] = [
                    'name' => $file['name'] ?? '',
                    'type' => $file['type'] ?? '',
                    'tmp_name' => $file['tmp_name'] ?? '',
                    'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $file['size'] ?? 0,
                ];
            } elseif (\is_array($file)) {
                $normalized[$field] = $file;
            }
        }

        return $normalized;
    }

    /**
     * Takes a snapshot of the current process superglobals.
     *
     * @return array<string, mixed>
     */
    private static function snapshotGlobals(): array
    {
        $backup = [];
        foreach (self::SUPERGLOBALS as $name) {
            $backup[$name] = $GLOBALS[$name] ?? [];
        }
        $backup['argv'] = $GLOBALS['argv'] ?? [];
        $backup['argc'] = $GLOBALS['argc'] ?? 0;
        return $backup;
    }

    /**
     * Applies the context state to PHP superglobals.
     *
     * @param array<string, mixed> $state
     */
    private static function applyStateToPHPGlobals(array $state): void
    {
        foreach (self::SUPERGLOBALS as $name) {
            if (\key_exists($name, $state)) {
                $GLOBALS[$name] = $state[$name];
            }
        }

        if (\key_exists('argv', $state)) {
            $GLOBALS['argv'] = $state['argv'];
        }
        if (\key_exists('argc', $state)) {
            $GLOBALS['argc'] = $state['argc'];
        }
    }

    /**
     * Restores the superglobals from the backup.
     *
     * @param array<string, mixed> $backup
     */
    private static function restoreGlobals(array $backup): void
    {
        foreach (self::SUPERGLOBALS as $name) {
            if (\key_exists($name, $backup)) {
                $GLOBALS[$name] = $backup[$name];
            }
        }

        if (\key_exists('argv', $backup)) {
            $GLOBALS['argv'] = $backup['argv'];
        }
        if (\key_exists('argc', $backup)) {
            $GLOBALS['argc'] = $backup['argc'];
        }
    }

    /**
     * Dispatches listeners for an event.
     *
     * @param string      $event
     * @param array       $state   Passed by reference so listeners can mutate it.
     * @param object|null $request
     * @param object|null $psr7
     */
    private static function dispatch(string $event, array &$state, ?object $request): void
    {
        foreach (self::$listeners[$event] as $handler) {
            try {
                if ($event === 'capture') {
                    $handler($state, $request);
                } else {
                    $handler($state);
                }

                if ($event === 'capture') {
                    self::applyStateToPHPGlobals($state);
                }
            } catch (Throwable $e) {
                trigger_error(
                    "RequestContext: exception in '$event' listener: " .
                    $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                    E_USER_WARNING
                );
            }
        }
    }

    /**
     * Returns the current coroutine context entry, or an empty array if none exists.
     *
     * @return array<string, mixed>
     */
    private static function currentEntry(): array
    {
        if (!self::isSwooleAvailable()) {
            return [];
        }

        $ctx = Coroutine::getContext();
        return $ctx[self::CTX_KEY] ?? [];
    }

    /**
     * Throws if there is no active context in the current coroutine.
     */
    private static function assertContext(): void
    {
        if (!self::isSwooleAvailable()) {
            return;
        }

        $ctx = Coroutine::getContext();
        if (!isset($ctx[self::CTX_KEY])) {
            throw new \RuntimeException(
                'No active RequestContext in the current coroutine ' .
                '(cid=' . Coroutine::getCid() . '). ' .
                'Call RequestContext::capture() first.'
            );
        }
    }

    /**
     * Throws if we are not inside a Swoole coroutine.
     */
    private static function assertCoroutine(): void
    {
        if (!self::isSwooleAvailable()) {
            throw new \RuntimeException(
                'RequestContext requires the Swoole extension.'
            );
        }

        if (Coroutine::getCid() <= 0) {
            throw new \RuntimeException(
                'RequestContext::capture() must be called inside a Swoole coroutine.'
            );
        }
    }

    /**
     * Checks, with cache, whether Swoole/OpenSwoole is available.
     */
    private static function isSwooleAvailable(): bool
    {
        if (self::$swooleAvailable === null) {
            self::$swooleAvailable = extension_loaded('swoole') || extension_loaded('openswoole');
        }

        return self::$swooleAvailable;
    }

    // Non-instantiable class.
    private function __construct()
    {
    }
}
