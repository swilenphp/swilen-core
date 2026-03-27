<?php

namespace Swilen\Arthropod\Runtime;

use OpenSwoole\Atomic;
use OpenSwoole\Constant;
use OpenSwoole\Http\Request as SwooleRequest;
use OpenSwoole\Http\Response as SwooleResponse;
use OpenSwoole\Http\Server;
use Swilen\Arthropod\Contract\HttpKernel;
use Swilen\Arthropod\Contract\RuntimeContract;
use Swilen\Arthropod\Events\ServerListeningEvent;
use Swilen\Arthropod\Events\ServerShuttingDownEvent;
use Swilen\Arthropod\Events\ServerStartedEvent;
use Swilen\Arthropod\Events\ServerStartingEvent;
use Swilen\Arthropod\Events\ServerStoppedEvent;
use Swilen\Events\Dispatcher;
use Swilen\Http\Common\SupportResponse;
use Swilen\Http\Events\RequestReceivedEvent;
use Swilen\Http\Events\ResponseSendingEvent;
use Swilen\Http\Events\ResponseSentEvent;
use Swilen\Http\Events\ResponseTerminatedEvent;
use Swilen\Http\Request;
use Swilen\Http\Response;
use Swilen\Shared\Container\Container;

class Swoole implements RuntimeContract
{
    protected string $host = '0.0.0.0';

    protected int $port = 8080;

    protected array $options = [];

    protected array $settings = [];

    protected readonly Atomic $isStarted;

    /**
     * The Swoole HTTP server instance.
     * 
     * @var Server
     * @readonly
     */
    protected Server $server;

    /**
     * The application container instance.
     * 
     * @var \Swilen\Container\Container
     * @readonly
     */
    protected Container $container;

    /**
     * Event dispatcher for server events.
     * 
     * @var \Swilen\Events\Dispatcher
     */
    protected Dispatcher $events;

    public function __construct(
        string $host = '0.0.0.0',
        int $port = 8080,
        array $settings = [],
        array $options = [],
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->settings = $settings;
        $this->options = $options;
        $this->isStarted = new Atomic(0);
    }

    public function updateSettings(array $settings): void
    {
        if (!empty($settings)) {
            if ((int) ($settings['port'] ?? 0) > 0) {
                $this->port = (int) $settings['port'];
            }
            if (isset($settings['host']) && \is_string($settings['host'])) {
                $this->host = $settings['host'];
            }
        }
    }

    /**
     * Set the container instance for the runtime.
     *
     * @param \Swilen\Container\Container $container
     *
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
        $this->events = $container->get(Dispatcher::class);
    }

    /**
     * Run the application with the given HTTP kernel.
     *
     * @param \Swilen\Arthropod\Contract\HttpKernel $kernel
     *
     * @return void
     */
    public function run(HttpKernel $kernel): void
    {
        if ($this->isStarted->get() === 1) {
            throw new \RuntimeException('Server is already running.');
        }

        $this->ensureDependencies();

        $this->server = new Server($this->host, $this->port, Server::SIMPLE_MODE, Constant::SOCK_TCP);

        $this->events->dispatch(new ServerStartingEvent(
            $this->host,
            $this->port,
            $this->settings
        ));

        $this->registerServerEvents($kernel);

        $this->server->start();
        $this->isStarted->set(1);
    }

    // Check for required extensions and dependencies before starting the server
    protected function ensureDependencies(): void
    {
        if (!\extension_loaded('openswoole')) {
            throw new \RuntimeException('The Swoole extension is required to run the server runtime.');
        }

        if ($this->container === null) {
            throw new \RuntimeException('Container instance must be set before running the server.');
        }

        if (!$this->container->has(Dispatcher::class)) {
            throw new \RuntimeException('Event dispatcher must be registered in the container.');
        }

        if (!\extension_loaded('pcntl')) {
            $this->log('Warning: The pcntl extension is not loaded. Graceful reload and shutdown will not be available.');
        }

        if (!\extension_loaded('posix')) {
            $this->log('Warning: The posix extension is not loaded. Graceful reload and shutdown will not be available.');
        }
    }

    protected function registerServerEvents(HttpKernel $kernel): void
    {
        $this->server->on('Start', function (Server $server) {
            $this->events->dispatch(new ServerStartedEvent($this->host, $this->port));
            $this->events->dispatch(new ServerListeningEvent($this->host, $this->port));

            $this->log("Server started listening on {$this->host}:{$this->port}");
        });

        $this->server->on('Request', function (SwooleRequest $srequest, SwooleResponse $sresponse) use ($kernel) {
            ob_start();

            $response = null;

            // Capture the request context for the current Swoole request
            $request = RequestContext::capture($srequest);

            try {
                $this->events->dispatch(new RequestReceivedEvent($request));

                $response = $kernel->handle($request);

                $this->events->dispatch(new ResponseSendingEvent($response));

                $this->convertNativeResponseToSwoole($response, $sresponse);

                $this->events->dispatch(new ResponseSentEvent($response));
            } catch (\Throwable $e) {
                $errorResponse = $this->handleException($e);
                $this->convertNativeResponseToSwoole($errorResponse, $sresponse);
            } finally {
                $capturedOutput = ob_get_clean();

                if (!empty($capturedOutput)) {
                    $this->log($capturedOutput);
                }

                if ($request && $response) {
                    $this->events->dispatch(new ResponseTerminatedEvent($request, $response));
                }
            }

            RequestContext::free();
            SupportResponse::closeOutputBuffer();
        });

        $this->server->on('shutdown', function (Server $server) {
            $this->events->dispatch(new ServerShuttingDownEvent());
            $this->events->dispatch(new ServerStoppedEvent());

            $this->log('Swoole server shutdown');
        });

        $this->server->on('close', function (Server $server, int $fd) {
            $this->log("Connection closed: {$fd}");
        });

        if (isset($this->options['enable_reload']) && $this->options['enable_reload']) {
            $this->registerReloadSignals();
        }
    }

    protected function convertNativeResponseToSwoole(
        Response $response,
        SwooleResponse $sresponse,
    ): void {
        $sresponse->status($response->getStatusCode());
        $contentType = 'text/html; charset=UTF-8';

        if ($response->headers->has('Content-Type')) {
            $contentType = $response->headers->get('Content-Type');
        }

        $sresponse->header('Content-Type', $contentType);

        $response->headers->each(function (string $name, string $value) use ($sresponse) {
            if (strtolower($name) !== 'content-type') {
                $sresponse->header($name, $value);
            }
        });

        $sresponse->end($response->getBody());
    }

    protected function handleException(\Throwable $e): Response
    {
        try {
            $handler = $this->container->make(\Swilen\Arthropod\Contract\ExceptionHandler::class);

            return $handler->render($e);
        } catch (\Throwable $handlerException) {
            return new Response(
                '<html><body><h1>500 Internal Server Error</h1><p>' .
                htmlspecialchars($e->getMessage()) .
                '</p></body></html>',
                500,
                ['Content-Type' => 'text/html'],
            );
        }
    }

    /**
     * Register signal handlers for graceful reload and shutdown.
     */
    protected function registerReloadSignals(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGUSR1, function () {
            $this->events->dispatch(new \Swilen\Arthropod\Events\ServerReloadingEvent());
            $this->log('Received SIGUSR1, reloading workers...');
            $this->server->reload();
            $this->log('Workers reloaded');
        });

        pcntl_signal(SIGUSR2, function () {
            $this->log('Received SIGUSR2, gracefully shutting down...');
            $this->server->shutdown();
        });
    }

    /**
     * Trigger a reload of the Swoole server workers.
     */
    public function reload(): void
    {
        if (extension_loaded('posix')) {
            posix_kill(posix_getpid(), SIGUSR1);
        }
    }

    /**
     * Trigger a graceful shutdown of the Swoole server.
     */
    public function shutdown(): void
    {
        if (extension_loaded('posix')) {
            posix_kill(posix_getpid(), SIGUSR2);
        }
    }

    protected function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] [INFO] {$message}" . PHP_EOL;
    }
}
