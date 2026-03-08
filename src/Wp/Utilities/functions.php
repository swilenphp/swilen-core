<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress Misc Utilities,
 * providing async-compatible implementations for Swoole environment.
 *
 * @package Swilen\Wp\Utilities
 */

if (!defined('ABSPATH')) {
    define('ABSPATH', base_path() . '/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!function_exists('wp_die')) {
    /**
     * Kill WordPress execution and display HTML message with error message.
     */
    function wp_die(string $message = '', string $title = '', array $args = []): void
    {
        $exitCode = $args['code'] ?? 500;

        if (!empty($args['response'])) {
            http_response_code($args['response']);
        }

        echo '<html><head><title>Error</title></head><body>';
        echo '<h1>' . esc_html($title ?: 'WordPress Error') . '</h1>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</body></html>';


        exit($exitCode);
    }
}

if (!function_exists('wp_nonce_field')) {
    /**
     * Retrieve or display nonce hidden field for forms.
     */
    function wp_nonce_field(string $action = '', string $name = '_wpnonce', bool $echo = true): string
    {
        $nonceField = '<input type="hidden" id="' . esc_attr($name) . '" name="' . esc_attr($name) . '" value="' . esc_attr(wp_create_nonce($action)) . '" />';

        if ($echo) {
            echo $nonceField;
        }

        return $nonceField;
    }
}

if (!function_exists('wp_create_nonce')) {
    /**
     * Creates a cryptographic token tied to a specific action, user, user session, and window of time.
     */
    function wp_create_nonce(string $action): string
    {
        $user = wp_get_current_user();
        $uid = $user->ID ?? 0;
        $session = app()->make('session') ?? '';

        $tick = floor(time() / (12 * 3600));

        return wp_hash($tick . '|' . $action . '|' . $uid . '|' . $session, 'nonce');
    }
}

if (!function_exists('wp_verify_nonce')) {
    /**
     * Verify that a correct security nonce was used with time limit.
     */
    function wp_verify_nonce(?string $nonce, string $action = ''): int|false
    {
        if ($nonce === null || $nonce === '') {
            return false;
        }

        $expected = wp_create_nonce($action);

        if (hash_equals($expected, $nonce)) {
            return 1;
        }

        return false;
    }
}

if (!function_exists('wp_hash')) {
    /**
     * Get hash of given string.
     */
    function wp_hash(string $data, string $scheme = 'auth'): string
    {
        $salt = app()->make('config')->get('app.secret', 'wp-salt-key');

        return hash_hmac('md5', $data, $salt);
    }
}

if (!function_exists('wp_get_current_user')) {
    /**
     * Retrieve the current user object.
     */
    function wp_get_current_user(): \WP_User
    {
        if (!app()->has('wp_current_user')) {
            app()->instance('wp_current_user', new \WP_User());
        }

        return app()->make('wp_current_user');
    }
}

if (!function_exists('get_current_user_id')) {
    /**
     * Retrieve the current user ID.
     */
    function get_current_user_id(): int
    {
        $user = wp_get_current_user();

        return $user->ID ?? 0;
    }
}

if (!function_exists('is_admin')) {
    /**
     * Determines whether the current request is an administrative interface page.
     */
    function is_admin(): bool
    {
        $request = app()->make('request');
        $uri = $request->getUri()->getPath();

        return str_starts_with($uri, '/wp-admin') || str_starts_with($uri, '/admin');
    }
}

if (!function_exists('is_user_logged_in')) {
    /**
     * Determines whether the current visitor is logged in.
     */
    function is_user_logged_in(): bool
    {
        $user = wp_get_current_user();

        return $user->ID > 0;
    }
}

if (!function_exists('is_multisite')) {
    /**
     * Determines whether the current installation is a multisite.
     */
    function is_multisite(): bool
    {
        return app()->make('config')->get('app.multisite', false);
    }
}

if (!function_exists('wp_redirect')) {
    /**
     * Redirects to another page.
     */
    function wp_redirect(string $location, int $status = 302): bool|int
    {
        // if (!app()->runningInConsole()) {
        //     header('Location: ' . $location, true, $status);
        // }

        return $status;
    }
}

if (!function_exists('wp_safe_redirect')) {
    /**
     * Redirects to another page, but only after validating the redirect URL.
     */
    function wp_safe_redirect(string $location, int $status = 302, string $safe_hosts = ''): bool|int
    {
        $location = wp_validate_redirect($location, home_url());

        return wp_redirect($location, $status);
    }
}

if (!function_exists('wp_validate_redirect')) {
    /**
     * Validates a URL for safe redirect.
     */
    function wp_validate_redirect(?string $url, string $default = ''): string
    {
        if (empty($url)) {
            return $default;
        }

        $url = esc_url_raw($url);

        if (empty($url)) {
            return $default;
        }

        $homeHost = parse_url(home_url(), PHP_URL_HOST);
        $redirectHost = parse_url($url, PHP_URL_HOST);

        if ($homeHost !== $redirectHost && !is_super_admin()) {
            return $default;
        }

        return $url;
    }
}

if (!function_exists('wp_json_encode')) {
    /**
     * Encode a variable into JSON, with some fixes for common quirks.
     */
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        if ($depth > max_json_depth()) {
            return false;
        }

        return json_encode($data, $options | JSON_THROW_ON_ERROR, $depth);
    }
}

if (!function_exists('max_json_depth')) {
    /**
     * Retrieve the maximum json decode depth.
     */
    function max_json_depth(): int
    {
        return app()->make('config')->get('app.json_depth', 512);
    }
}

if (!function_exists('wp_unslash')) {
    /**
     * Remove slashes from a string or recursively remove slashes from strings within an array.
     */
    function wp_unslash(mixed $value): mixed
    {
        return stripslashes_deep($value);
    }
}

if (!function_exists('stripslashes_deep')) {
    /**
     * Recursively removes slashes from values.
     */
    function stripslashes_deep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('stripslashes_deep', $value);
        }

        return is_string($value) ? stripslashes($value) : $value;
    }
}

if (!function_exists('wp_slash')) {
    /**
     * Adds slashes to a string or recursively adds slashes to strings within an array.
     */
    function wp_slash(mixed $value): mixed
    {
        return addslashes_deep($value);
    }
}

if (!function_exists('addslashes_deep')) {
    /**
     * Recursively adds slashes to values.
     */
    function addslashes_deep(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map('addslashes_deep', $value);
        }

        return is_string($value) ? addslashes($value) : $value;
    }
}

if (!function_exists('wp_json_send_response')) {
    /**
     * Send a JSON response back to an Ajax request.
     */
    function wp_json_send_response(mixed $data, array $headers = []): void
    {
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }

        header('Content-Type: app/json; charset=' . get_option('blog_charset', 'UTF-8'));
        echo wp_json_encode($data);
    }
}

if (!function_exists('wp_send_json')) {
    /**
     * Send a JSON response back to an Ajax request.
     */
    function wp_send_json(mixed $data): void
    {
        wp_json_send_response($data);
    }
}

if (!function_exists('wp_send_json_success')) {
    /**
     * Send a JSON response back to an Ajax request, indicating success.
     */
    function wp_send_json_success(mixed $data = null, int $status = 200): void
    {
        if ($data !== null) {
            $data = ['data' => $data];
        }

        $data = array_merge(['success' => true], $data ?? []);

        http_response_code($status);
        wp_json_send_response($data);
    }
}

if (!function_exists('wp_send_json_error')) {
    /**
     * Send a JSON response back to an Ajax request, indicating error.
     */
    function wp_send_json_error(mixed $data = null, int $status = 400): void
    {
        if ($data !== null) {
            $data = ['data' => $data];
        }

        $data = array_merge(['success' => false], $data ?? []);

        http_response_code($status);
        wp_json_send_response($data);
    }
}

class WP_User
{
    public int $ID = 0;
    public string $user_login = '';
    public string $user_pass = '';
    public string $user_nicename = '';
    public string $user_email = '';
    public string $user_url = '';
    public string $user_registered = '';
    public string $user_activation_key = '';
    public int $user_status = 0;
    public string $display_name = '';
    public string $nickname = '';
    public string $first_name = '';
    public string $last_name = '';
    public string $description = '';
    public string $rich_editing = '';
    public string $syntax_highlighting = '';
    public string $comment_shortcuts = '';
    public string $admin_color = '';
    public int $use_ssl = 0;
    public int $show_admin_bar_front = 0;
    public string $locale = '';
    public array $meta = [];

    public function __get(string $key): mixed
    {
        return $this->meta[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->meta[$key]);
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Determines if the current user is a network (super) admin.
     */
    function is_super_admin(int|bool $user_id = false): bool
    {
        if (!is_multisite()) {
            return true;
        }

        $user_id = $user_id ?: get_current_user_id();

        if (!$user_id) {
            return false;
        }

        return cache()->getOrSet("super_admin_{$user_id}", function () {
            return [false, 3600];
        });
    }
}
