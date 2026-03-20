<?php

namespace Swilen\Arthropod\Middleware;

use Swilen\Container\Container;
use Swilen\Http\Request;

class LoggerMiddleware
{
    /**
     * The container instance.
     *
     * @var \Swilen\Container\Container
     */
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle incoming request for log request.
     *
     * @param \Swilen\Http\Request $request
     * @param \Closure             $next
     *
     * @return \Swilen\Http\Response
     */
    public function handle(Request $request, \Closure $next)
    {
        $startTime = microtime(true);

        $response = $next($request);

        $latency = round((microtime(true) - $startTime) * 1000, 2);

        $this->writeToConsole(
            $request->getMethod(),
            $request->getPathInfo(),
            $response->getStatusCode(),
            $latency,
            $request->getClientIp() ?? '127.0.0.1'
        );

        return $response;
    }

    protected function writeToConsole(string $method, string $path, int $status, float $latency, string $ip)
    {
        $cStatus = match (true) {
            $status >= 500 => "\033[41;37m $status \033[0m",
            $status >= 400 => "\033[43;30m $status \033[0m",
            $status >= 300 => "\033[44;37m $status \033[0m",
            default        => "\033[42;30m $status \033[0m",
        };

        $cMethod  = "\033[1;35m" . str_pad($method, 6) . "\033[0m";
        $cLatency = $latency > 500 ? "\033[31m{$latency}ms\033[0m" : "\033[32m{$latency}ms\033[0m";
        $time     = "\033[90m" . date('H:i:s') . "\033[0m";

        $line = sprintf(
            "[%s] %s %s | %10s | %s %s",
            $time,
            $cMethod,
            $cStatus,
            $cLatency,
            $path,
            PHP_EOL
        );

        if (defined('STDOUT')) {
            fwrite(STDOUT, $line);
        } else {
            file_put_contents('php://stdout', $line);
        }
    }
}
