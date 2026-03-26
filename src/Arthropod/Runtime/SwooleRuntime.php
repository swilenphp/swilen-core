<?php

namespace Swilen\Arthropod\Runtime;

use OpenSwoole\Atomic;
use Swilen\Arthropod\Application;
use Swilen\Arthropod\Contract\HttpKernel;
use Swilen\Arthropod\Contract\RuntimeContract;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Shared\Arthropod\Application as ArthropodApplication;

class SwooleRuntime implements RuntimeContract
{
    protected Application $app;

    protected string $host = '0.0.0.0';

    protected int $port = 8080;

    protected array $options = [];

    protected array $settings = [];

    protected Atomic $booted;

    public function __construct(
        string $host = '0.0.0.0',
        int $port = 8080,
        array $settings = [],
        array $options = []
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->settings = $settings;
        $this->options = $options;
    }

    public function bootstrap(): void
    {
        if ($this->booted->get()) {
            return;
        }

        $this->app = new Application(
            $this->options['base_path'] ?? getcwd()
        );

        $this->app->useBasePath($this->options['base_path'] ?? getcwd());

        if (isset($this->options['environment'])) {
            $this->app->useEnvironment($this->options['environment']);
        }

        if (isset($this->options['environment_file'])) {
            $this->app->useEnvironmentFile($this->options['environment_file']);
        }

        if (isset($this->options['environment_path'])) {
            $this->app->useEnvironmentPath($this->options['environment_path']);
        }

        $this->app->setup();
        $this->app->registerProviders();
        $this->app->boot();

        $this->booted = true;
    }

    public function run(HttpKernel $kernel): void
    {
        $this->bootstrap();

        $serverSettings = array_merge([
            'host' => $this->host,
            'port' => $this->port,
            'mode' => SWOOLE_PROCESS,
            'sock_type' => SWOOLE_SOCK_TCP,
        ], $this->settings);

        $server = new Server(
            $serverSettings['host'],
            $serverSettings['port'],
            $serverSettings['mode'],
            $serverSettings['sock_type']
        );

        unset($serverSettings['host'], $serverSettings['port'], $serverSettings['mode'], $serverSettings['sock_type']);

        $server->set($serverSettings);

        $this->registerServerEvents($server, $kernel);

        $this->log("Starting Swoole HTTP server on {$this->host}:{$this->port}");

        $server->start();
    }

    protected function registerServerEvents(\OpenSwoole\Http\Server $server, HttpKernel $kernel): void
    {
        $app = $this->app;
        $logger = $this->logger();

        $server->on('start', function (\OpenSwoole\Http\Server $server) use ($logger) {
            $this->log($server->master_pid
                ? "Swoole master process started with PID: {$server->master_pid}"
                : 'Swoole master process started'
            );
        });

        $server->on('managerStart', function (\OpenSwoole\Http\Server $server) use ($logger) {
            $this->log("Swoole manager process started with PID: {$server->manager_pid}");
        });

        $server->on('workerStart', function (\OpenSwoole\Http\Server $server, int $workerId) use ($logger) {
            $this->log("Worker #{$workerId} started (PID: {$server->worker_pid})");
        });

        $server->on('workerStop', function (\OpenSwoole\Http\Server $server, int $workerId) use ($logger) {
            $this->log("Worker #{$workerId} stopped");
        });

        $server->on('request', function (\OpenSwoole\Http\Request $swooleRequest, \OpenSwoole\Http\Response $swooleResponse) use ($app, $kernel, $logger) {
            $requestId = uniqid('req_', true);

            $this->log("[{$requestId}] Incoming request: {$swooleRequest->server['request_method']} {$swooleRequest->server['request_uri']}");

            ob_start();

            try {
                $request = $this->convertSwooleRequestToNative($swooleRequest);

                $response = $kernel->handle($request);

                $this->convertNativeResponseToSwoole($response, $swooleResponse);

                $this->log("[{$requestId}] Response sent: {$response->getStatusCode()}");
            } catch (\Throwable $e) {
                $this->log("[{$requestId}] Exception: {$e->getMessage()}");

                $errorResponse = $this->handleException($e);
                $this->convertNativeResponseToSwoole($errorResponse, $swooleResponse);
            } finally {
                $capturedOutput = ob_get_clean();

                if (!empty($capturedOutput)) {
                    $this->log("[{$requestId}] Captured output: " . strlen($capturedOutput) . " bytes");
                }
            }

            $this->resetRequestState();
        });

        $server->on('shutdown', function (\OpenSwoole\Http\Server $server) use ($logger) {
            $this->log('Swoole server shutdown');
        });

        $server->on('close', function (\OpenSwoole\Http\Server $server, int $fd) use ($logger) {
            $this->log("Connection closed: {$fd}");
        });

        if (isset($this->options['enable_reload']) && $this->options['enable_reload']) {
            $this->registerReloadSignals($server);
        }
    }

    protected function convertSwooleRequestToNative(\OpenSwoole\Http\Request $swooleRequest): Request
    {
        $server = $this->convertServerVars($swooleRequest);
        $headers = $this->convertHeaders($swooleRequest);
        $request = $this->convertRequestVars($swooleRequest);
        $query = $this->convertQueryVars($swooleRequest);
        $body = $this->getRequestBody($swooleRequest);

        $request = new Request($server, [], $request, $query, $body);

        $request->withMethod($server['REQUEST_METHOD']);

        return $request;
    }

    protected function convertServerVars(\OpenSwoole\Http\Request $swooleRequest): array
    {
        $server = [];

        if (isset($swooleRequest->server)) {
            foreach ($swooleRequest->server as $key => $value) {
                $server[strtoupper($key)] = $value;
            }
        }

        $server += [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_SOFTWARE' => 'Swoole/' . SWOOLE_VERSION,
            'REMOTE_ADDR' => $swooleRequest->header['x-real-ip'] ?? '127.0.0.1',
            'SERVER_NAME' => $swooleRequest->header['host'] ?? 'localhost',
        ];

        return $server;
    }

    protected function convertHeaders(\OpenSwoole\Http\Request $swooleRequest): array
    {
        $headers = [];

        if (isset($swooleRequest->header)) {
            foreach ($swooleRequest->header as $key => $value) {
                $headers['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
            }
        }

        return $headers;
    }

    protected function convertRequestVars(\OpenSwoole\Http\Request $swooleRequest): array
    {
        $request = [];

        if (isset($swooleRequest->post)) {
            $request = (array) $swooleRequest->post;
        }

        if (isset($swooleRequest->rawContent()) && empty($request)) {
            $contentType = $swooleRequest->header['content-type'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $json = json_decode($swooleRequest->rawContent(), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                    $request = $json;
                }
            }
        }

        return $request;
    }

    protected function convertQueryVars(\OpenSwoole\Http\Request $swooleRequest): array
    {
        if (isset($swooleRequest->get)) {
            return (array) $swooleRequest->get;
        }

        return [];
    }

    protected function getRequestBody(\OpenSwoole\Http\Request $swooleRequest): ?string
    {
        $rawContent = $swooleRequest->rawContent();

        return $rawContent !== '' ? $rawContent : null;
    }

    protected function convertNativeResponseToSwoole(Response $response, \OpenSwoole\Http\Response $swooleResponse): void
    {
        $swooleResponse->status($response->getStatusCode());

        if ($response->hasHeader('Content-Type')) {
            $contentType = $response->headers->get('Content-Type');
        } else {
            $contentType = 'text/html; charset=' . $response->charset;
            $swooleResponse->header('Content-Type', $contentType);
        }

        $response->headers->each(function (string $name, string $value) use ($swooleResponse) {
            if (strtolower($name) !== 'content-type') {
                $swooleResponse->header($name, $value);
            }
        });

        $body = $response->getBody();

        if ($body !== null) {
            $swooleResponse->end($body);
        } else {
            $swooleResponse->end('');
        }
    }

    protected function handleException(\Throwable $e): Response
    {
        if (!isset($this->app)) {
            return new Response(
                '<html><body><h1>500 Internal Server Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>',
                500,
                ['Content-Type' => 'text/html']
            );
        }

        try {
            $handler = $this->app->make(\Swilen\Arthropod\Contract\ExceptionHandler::class);

            return $handler->render($e);
        } catch (\Throwable $handlerException) {
            return new Response(
                '<html><body><h1>500 Internal Server Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p></body></html>',
                500,
                ['Content-Type' => 'text/html']
            );
        }
    }

    protected function resetRequestState(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_FILES = [];

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    protected function registerReloadSignals(\OpenSwoole\Http\Server $server): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGUSR1, function () use ($server) {
            $this->log('Received SIGUSR1, reloading workers...');
            $server->reload();
            $this->log('Workers reloaded');
        });

        pcntl_signal(SIGUSR2, function () use ($server) {
            $this->log('Received SIGUSR2, gracefully shutting down...');
            $server->shutdown();
        });
    }

    public function reload(): void
    {
        if (extension_loaded('posix')) {
            posix_kill(posix_getpid(), SIGUSR1);
        }
    }

    public function shutdown(): void
    {
        if (extension_loaded('posix')) {
            posix_kill(posix_getpid(), SIGUSR2);
        }
    }

    protected function logger(): \Psr\Log\LoggerInterface
    {
        return new class implements \Psr\Log\LoggerInterface {
            public function emergency(\Stringable|string $message, array $context = []): void
            {
                $this->log('EMERGENCY', $message, $context);
            }

            public function alert(\Stringable|string $message, array $context = []): void
            {
                $this->log('ALERT', $message, $context);
            }

            public function critical(\Stringable|string $message, array $context = []): void
            {
                $this->log('CRITICAL', $message, $context);
            }

            public function error(\Stringable|string $message, array $context = []): void
            {
                $this->log('ERROR', $message, $context);
            }

            public function warning(\Stringable|string $message, array $context = []): void
            {
                $this->log('WARNING', $message, $context);
            }

            public function notice(\Stringable|string $message, array $context = []): void
            {
                $this->log('NOTICE', $message, $context);
            }

            public function info(\Stringable|string $message, array $context = []): void
            {
                $this->log('INFO', $message, $context);
            }

            public function debug(\Stringable|string $message, array $context = []): void
            {
                $this->log('DEBUG', $message, $context);
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $timestamp = date('Y-m-d H:i:s');
                $messageStr = (string) $message;

                if (!empty($context)) {
                    $messageStr .= ' ' . json_encode($context);
                }

                echo "[{$timestamp}] [{$level}] {$messageStr}" . PHP_EOL;
            }
        };
    }

    protected function log(string $message): void
    {
        if (isset($this->options['logger'])) {
            $this->options['logger']->info($message);
        } else {
            $timestamp = date('Y-m-d H:i:s');
            echo "[{$timestamp}] [INFO] {$message}" . PHP_EOL;
        }
    }

    public function getApplication(): ?Application
    {
        return $this->app ?? null;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    public static function create(
        string $host = '0.0.0.0',
        int $port = 8080,
        array $settings = [],
        array $options = []
    ): self {
        return new self($host, $port, $settings, $options);
    }
}
