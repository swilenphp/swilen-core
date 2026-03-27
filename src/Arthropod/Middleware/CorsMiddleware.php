<?php

namespace Swilen\Arthropod\Middleware;

use Swilen\Container\Container;
use Swilen\Http\Common\Method;
use Swilen\Http\Request;
use Swilen\Http\Response;

class CorsMiddleware
{
    /**
     * The container instance.
     */
    protected Container $container;

    /**
     * Settings resolved from config file.
     */
    protected array $settings;

    /**
     * Create new CorsMiddleware.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;

        $defaults = $this->getDefaultConfig();
        $this->settings = $container->has('config')
            ? $container->make('config')->get('cors', []) + $defaults
            : $this->getDefaultConfig();
    }

    /**
     * Handle incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        if (!$this->shouldRun($request)) {
            return $next($request);
        }

        if ($this->isPreflightRequest($request)) {
            return $this->handlePreflightRequest($request);
        }

        return tap($next($request), function (Response $response) use ($request) {
            $this->appendCorsHeaders($response, $request);
        });
    }

    /**
     * Append CORS headers to the response.
     */
    protected function appendCorsHeaders(Response $response, Request $request): void
    {
        $origin = $this->getValidOrigin($request);

        $response->withHeaders([
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Credentials' => $this->settings['allow_credentials'] ? 'true' : 'false',
        ]);

        if ($this->settings['exposed_headers']) {
            $response->withHeaders([
                'Access-Control-Expose-Headers' => \implode(', ', $this->settings['exposed_headers'])
            ]);
        }
    }

    /**
     * Handle the OPTIONS preflight request.
     */
    protected function handlePreflightRequest(Request $request): Response
    {
        return new Response(null, 204, [
            'Access-Control-Allow-Origin' => $this->getValidOrigin($request),
            'Access-Control-Allow-Methods' => \implode(', ', $this->settings['methods']),
            'Access-Control-Allow-Headers' => \implode(', ', $this->settings['headers']),
            'Access-Control-Allow-Credentials' => $this->settings['allow_credentials'] ? 'true' : 'false',
            'Access-Control-Max-Age' => $this->settings['max_age'],
        ]);
    }

    /**
     * Determine the Origin header value based on settings.
     */
    protected function getValidOrigin(Request $request): string
    {
        $origin = $request->headers->get('Origin');

        if (\in_array('*', $this->settings['origin'])) {
            return '*';
        }

        return \in_array($origin, $this->settings['origin']) ? $origin : 'null';
    }

    protected function isPreflightRequest(Request $request): bool
    {
        return $request->getMethod() === Method::OPTIONS && $request->headers->has('Access-Control-Request-Method');
    }

    protected function shouldRun(Request $request): bool
    {
        return $request->headers->has('Origin');
    }

    /**
     * Default configuration if none is provided.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'origin' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'],
            'headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'allow_credentials' => false,
            'max_age' => 86400,
        ];
    }
}
