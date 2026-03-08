<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress REST API Helpers,
 * providing REST route registration for async Swoole environment.
 *
 * @package Swilen\Wp
 */

use Swilen\Http\Request;
use Swilen\Routing\Router;

global $wp_rest_server;

if (!isset($wp_rest_server)) {
    $wp_rest_server = null;
}

if (!function_exists('register_rest_route')) {
    /**
     * Register a REST API route.
     */
    function register_rest_route(string $namespace, string $route, array $args = [], bool $override = false): bool
    {
        return rest_get_server()->register_route($namespace, $route, $args, $override);
    }
}

if (!function_exists('rest_ensure_response')) {
    /**
     * Ensures a REST response.
     */
    function rest_ensure_response(mixed $response): \WP_REST_Response
    {
        if (is_wp_error($response)) {
            $status = $response->get_error_data()['status'] ?? 500;
            return new \WP_REST_Response(
                ['code' => $response->get_error_code(), 'message' => $response->get_error_message(), 'data' => $response->get_error_data()],
                $status
            );
        }

        if ($response instanceof \WP_REST_Response) {
            return $response;
        }

        return new \WP_REST_Response($response, 200);
    }
}

if (!function_exists('rest_url')) {
    /**
     * Gets the REST URL.
     */
    function rest_url(string $path = '', string $scheme = 'https'): string
    {
        $path = '/' . ltrim($path, '/');

        $url = get_option('rest_url');

        if (empty($url)) {
            $url = site_url(rest_get_url_prefix() . '/', $scheme);
        }

        $url = apply_filters('rest_url', $url, $path, $scheme);

        return $url . ltrim($path, '/');
    }
}

if (!function_exists('register_rest_field')) {
    /**
     * Register a REST field.
     */
    function register_rest_field(string $object_type, string $field_name, array $args = []): void
    {
        $args = wp_parse_args($args, [
            'get_callback' => null,
            'update_callback' => null,
            'schema' => null,
        ]);

        do_action('register_rest_field', $object_type, $field_name, $args);
    }
}

if (!function_exists('rest_get_server')) {
    /**
     * Get the REST server instance.
     */
    function rest_get_server(): \WP_REST_Server
    {
        global $wp_rest_server;

        if ($wp_rest_server === null) {
            $wp_rest_server = new \WP_REST_Server();
        }

        return $wp_rest_server;
    }
}

if (!function_exists('rest_get_url_prefix')) {
    /**
     * Get the REST URL prefix.
     */
    function rest_get_url_prefix(): string
    {
        return apply_filters('rest_url_prefix', 'wp-json');
    }
}

if (!function_exists('rest_is_valid_callback')) {
    /**
     * Check if a callback is valid.
     */
    function rest_is_valid_callback(callable $callback): bool
    {
        return is_callable($callback);
    }
}

if (!function_exists('rest_validate_request_arg')) {
    /**
     * Validate a request argument.
     */
    function rest_validate_request_arg(mixed $value, \WP_REST_Request $request, string $param): \WP_Error|bool
    {
        return true;
    }
}

if (!function_exists('rest_sanitize_request_arg')) {
    /**
     * Sanitize a request argument.
     */
    function rest_sanitize_request_arg(mixed $value, \WP_REST_Request $request, string $param): mixed
    {
        return $value;
    }
}

if (!function_exists('register_api_class')) {
    /**
     * Register the core REST API classes.
     */
    function register_api_class(): void
    {
        do_action('rest_api_init');
    }
}

class WP_REST_Response implements \JsonSerializable
{
    protected $data;
    protected $headers = [];
    protected $status;

    public function __construct(mixed $data = null, int $status = 200)
    {
        $this->data = $data;
        $this->status = $status;
    }

    public function get_data(): mixed
    {
        return $this->data;
    }

    public function set_data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function get_status(): int
    {
        return $this->status;
    }

    public function set_status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function get_headers(): array
    {
        return $this->headers;
    }

    public function header(string $key, string $value, bool $replace = true): self
    {
        if ($replace || !isset($this->headers[$key])) {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    public function with_header(string $key, string $value): self
    {
        return $this->header($key, $value, true);
    }

    public function with_status(int $status): self
    {
        return $this->set_status($status);
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }
}

class WP_REST_Request
{
    protected $method = 'GET';
    protected $params = [];
    protected $url_params = [];
    protected $body = null;
    protected $headers = [];
    protected $route = '';
    public $matched_route = '';

    public function __construct(string $method = 'GET', array $params = [])
    {
        $this->method = $method;
        $this->params = $params;
    }

    public static function fromGlobals(): self
    {
        $request = app()->make(Request::class);

        $instance = new self($request->getMethod());

        $instance->headers = $request->getHeaders();
        $instance->body = $request->getBody()->getContents();
        $instance->route = $request->getUri()->getPath();

        parse_str($request->getUri()->getQuery(), $query);
        $instance->url_params = $query;

        if (!empty($instance->body)) {
            $json = json_decode($instance->body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $instance->params = $json;
            } else {
                parse_str($instance->body, $parsed);
                $instance->params = $parsed ?: [];
            }
        }

        return $instance;
    }

    public function get_method(): string
    {
        return $this->method;
    }

    public function get_headers(): array
    {
        return $this->headers;
    }

    public function get_header(string $key): ?string
    {
        return $this->headers[strtolower($key)] ?? null;
    }

    public function get_body(): ?string
    {
        return $this->body;
    }

    public function get_json_params(): ?array
    {
        return is_array($this->body) ? $this->body : null;
    }

    public function get_param(string $key): mixed
    {
        return $this->params[$key] ?? $this->url_params[$key] ?? null;
    }

    public function get_params(): array
    {
        return $this->params;
    }

    public function get_url_params(): array
    {
        return $this->url_params;
    }

    public function get_route(): string
    {
        return $this->route;
    }

    public function get_matched_route(): string
    {
        return $this->matched_route;
    }

    public function set_param(string $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }
}

class WP_REST_Server
{
    protected $routes = [];

    /**
     * Dispatches a REST request.
     */
    public function serve_request(): void
    {
        // This is handled by Swilen Router automatically.
        wp_die('REST requests should be handled by Swilen Router.', 'REST API Error', ['code' => 500]);
    }

    /**
     * Registers a route to the server.
     */
    public function register_route(string $namespace, string $route, array $args = [], bool $override = false): bool
    {
        if (empty($namespace)) {
            return false;
        }

        $prefix = rest_get_url_prefix();
        $path = '/' . ltrim($prefix, '/') . '/' . ltrim($namespace, '/') . '/' . ltrim($route, '/');
        $path = '/' . ltrim($path, '/');

        /** @var \Swilen\Routing\Router */
        $router = app()->make(Router::class);

        // Normalize endpoints
        if (isset($args['callback']) || isset($args['methods'])) {
            $endpoints = [$args];
        } else {
            $endpoints = $args;
        }

        foreach ($endpoints as $endpoint) {
            $methods = $endpoint['methods'] ?? 'GET';
            $methods = is_string($methods) ? explode(',', $methods) : (array) $methods;
            $callback = $endpoint['callback'] ?? null;

            if (!$callback) continue;

            $permission_callback = $endpoint['permission_callback'] ?? null;

            foreach ($methods as $method) {
                $router->addRoute(strtoupper(trim($method)), $path, function () use ($callback, $permission_callback, $path) {
                    $request = WP_REST_Request::fromGlobals();
                    $request->matched_route = $path;

                    if ($permission_callback && is_callable($permission_callback)) {
                        $permission = call_user_func($permission_callback, $request);
                        if (is_wp_error($permission)) {
                            $response = rest_ensure_response($permission);
                        } elseif ($permission === false) {
                            $response = rest_ensure_response(new WP_Error('rest_forbidden', 'Forbidden', ['status' => 403]));
                        }
                    }

                    if (!isset($response)) {
                        $response = call_user_func($callback, $request);
                        $response = rest_ensure_response($response);
                    }

                    return new \Swilen\Http\Response\JsonResponse(
                        $response->get_data(),
                        $response->get_status(),
                        $response->get_headers()
                    );
                });
            }
        }

        return true;
    }

    /**
     * Get the routes registered on the server.
     */
    public function get_routes(): array
    {
        return [];
    }
}


if (!function_exists('is_wp_error')) {
    /**
     * Check if value is a WP_Error.
     */
    function is_wp_error(mixed $thing): bool
    {
        return $thing instanceof WP_Error;
    }
}

class WP_Error
{
    protected $errors = [];
    protected $error_data = [];
    protected $wp_error = false;

    public function __construct(string $code, string $message = '', mixed $data = '')
    {
        $this->errors[$code][] = $message;

        if (!empty($data)) {
            $this->error_data[$code] = $data;
        }
    }

    public function get_error_codes(): array
    {
        return array_keys($this->errors);
    }

    public function get_error_code(): string
    {
        $codes = $this->get_error_codes();

        return !empty($codes) ? $codes[0] : '';
    }

    public function get_error_messages(string $code = ''): string
    {
        if (empty($code)) {
            $all_messages = [];
            foreach ((array) $this->errors as $code => $messages) {
                $all_messages = array_merge($all_messages, $messages);
            }
            return implode("\n", $all_messages);
        }

        return implode("\n", $this->errors[$code] ?? []);
    }

    public function get_error_message(string $code = ''): string
    {
        if (empty($code)) {
            $code = $this->get_error_code();
        }

        $messages = $this->errors[$code] ?? [];

        return !empty($messages) ? $messages[0] : '';
    }

    public function get_error_data(string $code = ''): mixed
    {
        if (empty($code)) {
            $code = $this->get_error_code();
        }

        return $this->error_data[$code] ?? null;
    }

    public function add(string $code, string $message, mixed $data = ''): void
    {
        $this->errors[$code][] = $message;

        if (!empty($data)) {
            $this->error_data[$code] = $data;
        }
    }
}

if (!function_exists('wp_parse_args')) {
    /**
     * Merge user defined arguments into defaults array.
     */
    function wp_parse_args(array $args, array $defaults = []): array
    {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('wp_json_encode')) {
    /**
     * Encode a variable into JSON.
     */
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        return json_encode($data, $options | JSON_THROW_ON_ERROR, $depth);
    }
}
