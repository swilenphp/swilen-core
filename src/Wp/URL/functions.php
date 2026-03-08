<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress URL Helpers,
 * providing URL generation and manipulation for async Swoole environment.
 *
 * @package Swilen\Wp
 */

use Swilen\Cache\Cache;

if (!function_exists('home_url')) {
    /**
     * Retrieves the home URL for the current site.
     */
    function home_url(string $path = '', string $scheme = 'https'): string
    {
        return get_site_url(null, $path, $scheme);
    }
}

if (!function_exists('get_site_url')) {
    /**
     * Retrieves the site URL for a given site ID.
     */
    function get_site_url(?int $blog_id = null, string $path = '', string $scheme = 'https'): string
    {
        $blog_id = $blog_id ?? get_current_blog_id();

        $siteurl = get_option('siteurl');

        if (empty($siteurl)) {
            $siteurl = app()->make('config')->get('app.url', 'http://localhost');
        }

        $url = set_url_scheme($siteurl, $scheme);

        if (!empty($blog_id) && $blog_id !== 1) {
            $url = get_mu_site_url($blog_id, $path, $scheme);
        }

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('site_url', $url, $path, $scheme, $blog_id);
    }
}

if (!function_exists('get_mu_site_url')) {
    /**
     * Retrieves the site URL for a multisite subsite.
     */
    function get_mu_site_url(int $blog_id, string $path, string $scheme): string
    {
        // $cacheKey = "mu_site_url_{$blog_id}_{$scheme}";

        // !TODO: Implement cache
        // $url = app()->make('cache')->remember($cacheKey, 3600, function () use ($blog_id, $path, $scheme) {
        //     global $wpdb;

        //     $blog = $wpdb->get_row($wpdb->prepare(
        //         "SELECT domain, path FROM {$wpdb->blogs} WHERE blog_id = %d",
        //         $blog_id
        //     ));

        //     if (!$blog) {
        //         return home_url($path, $scheme);
        //     }

        //     return 'https://' . $blog->domain . $blog->path;
        // });

        $url = 'https://' . app()->make('config')->get('app.url', 'http://localhost');
        if ($path) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return $url;
    }
}

if (!function_exists('site_url')) {
    /**
     * Retrieves the site URL.
     */
    function site_url(string $path = '', string $scheme = 'https'): string
    {
        return get_site_url(null, $path, $scheme);
    }
}

if (!function_exists('admin_url')) {
    /**
     * Retrieves the URL to the admin area.
     */
    function admin_url(string $path = '', string $scheme = 'admin'): string
    {
        $url = get_site_url(null, 'wp-admin/', $scheme);

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('admin_url', $url, $path, $scheme);
    }
}

if (!function_exists('includes_url')) {
    /**
     * Retrieves the URL to the includes directory.
     */
    function includes_url(string $path = ''): string
    {
        $url = get_site_url(null, WPINC . '/', 'https');

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('includes_url', $url, $path);
    }
}

if (!function_exists('content_url')) {
    /**
     * Retrieves the URL to the content directory.
     */
    function content_url(string $path = ''): string
    {
        $content = WP_CONTENT_DIR;

        $url = get_site_url(null, str_replace(ABSPATH, '', $content), 'https');

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('content_url', $url, $path);
    }
}

if (!function_exists('plugins_url')) {
    /**
     * Retrieves the URL to the plugins directory.
     */
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        $plugin = (string) $plugin;

        if (!empty($plugin)) {
            $mu_plugin_dir = defined('WPMU_PLUGIN_DIR') ? WPMU_PLUGIN_DIR : '';
            $plugin_dir = defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR : '';

            $mu_plugins_dir = defined('MU_PLUGIN_DIR') ? MU_PLUGIN_DIR : $mu_plugin_dir;
            $plugins_dir = defined('WP_PLUGIN_DIR') ? $plugin_dir : '';

            $mu_plugin_real = wp_normalize_path($mu_plugins_dir);
            $plugin_real = wp_normalize_path($plugins_dir);
            $plugin_real = wp_normalize_path($plugin);

            if ($mu_plugin_real && str_starts_with($plugin_real, $mu_plugin_real)) {
                $url = set_url_scheme(WPMU_PLUGIN_URL);
            } elseif ($plugin_real && str_starts_with($plugin_real, $plugin_real)) {
                $url = set_url_scheme(WP_PLUGIN_URL);
            } else {
                $url = content_url('plugins');
            }
        } else {
            $url = set_url_scheme(WP_PLUGIN_URL);
        }

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('plugins_url', $url, $path, $plugin);
    }
}

if (!function_exists('rest_url')) {
    /**
     * Retrieves the URL to the REST API.
     */
    function rest_url(string $path = '', string $scheme = 'https'): string
    {
        $path = '/' . ltrim($path, '/');

        $url = get_option('rest_url');

        if (empty($url)) {
            $url = get_site_url(null, 'wp-json/', $scheme);
        }

        $url = apply_filters('rest_url', $url, $path, $scheme);

        return $url . ltrim($path, '/');
    }
}

if (!function_exists('network_home_url')) {
    /**
     * Retrieves the network home URL.
     */
    function network_home_url(string $path = '', string $scheme = 'https'): string
    {
        if (!is_multisite()) {
            return home_url($path, $scheme);
        }

        $network = get_network();

        if (!empty($network->domain)) {
            $url = 'https://' . $network->domain . $network->path;
        } else {
            $url = home_url($scheme);
        }

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('network_home_url', $url, $path, $scheme);
    }
}

if (!function_exists('network_site_url')) {
    /**
     * Retrieves the network site URL.
     */
    function network_site_url(string $path = '', string $scheme = 'https'): string
    {
        if (!is_multisite()) {
            return site_url($path, $scheme);
        }

        $network = get_network();

        if (!empty($network->domain)) {
            $url = 'https://' . $network->domain . $network->path;
        } else {
            $url = site_url($scheme);
        }

        if ($path && is_string($path)) {
            $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        }

        return apply_filters('network_site_url', $url, $path, $scheme);
    }
}

if (!function_exists('get_current_blog_id')) {
    /**
     * Retrieves the current blog ID.
     */
    function get_current_blog_id(): int
    {
        // return app()->make('wp_blog_id');
        // Is harcoded, change in production
        return 10;
    }
}

if (!function_exists('get_option')) {
    /**
     * Retrieves an option value.
     */
    function get_option(string $option, mixed $default = false): mixed
    {
        $value = cache()->getOrSet("option_{$option}", function () use ($option, $default) {
            global $wpdb;

            if ($wpdb && !empty($wpdb->prefix)) {
                $row = $wpdb->get_row($wpdb->prepare(
                    "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                    $option
                ));

                if (is_object($row)) {
                    return maybe_unserialize($row->option_value);
                }
            }

            return [$default, 3600];
        });

        return $value !== null ? $value : $default;
    }
}

if (!function_exists('set_url_scheme')) {
    /**
     * Sets the URL scheme for a URL.
     */
    function set_url_scheme(string $url, string $scheme = ''): string
    {
        $scheme = strtolower($scheme);

        if (empty($scheme)) {
            if (is_ssl()) {
                $scheme = 'https';
            } elseif (str_starts_with($url, 'http://')) {
                $scheme = 'http';
            } elseif (str_starts_with($url, '//')) {
                $scheme = 'https';
            } else {
                $scheme = 'https';
            }
        } elseif ($scheme === 'https' && !is_ssl()) {
            $scheme = 'http';
        }

        if (str_starts_with($url, $scheme . '://')) {
            return $url;
        }

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            $url = preg_replace('#^(' . preg_quote('https://', '#') . '|' . preg_quote('http://', '#') . '|' . preg_quote('//', '#') . ')#', $scheme . '://', $url);
        }

        return $url;
    }
}

if (!function_exists('is_ssl')) {
    /**
     * Determines if SSL is being used.
     */
    function is_ssl(): bool
    {
        $request = app()->make('request');

        if ($request->hasHeader('X-Forwarded-Proto')) {
            return $request->getHeaderLine('X-Forwarded-Proto') === 'https';
        }

        if ($request->hasHeader('X-Forwarded-Ssl')) {
            return $request->getHeaderLine('X-Forwarded-Ssl') === 'on';
        }

        return $request->getUri()->getScheme() === 'https';
    }
}

if (!function_exists('get_network')) {
    /**
     * Retrieves the network object.
     */
    function get_network(): ?object
    {
        if (!is_multisite()) {
            return null;
        }

        $cacheKey = 'network_1';

        return cache()->getOrSet($cacheKey, function () {
            global $wpdb;

            $network = $wpdb->get_row("SELECT * FROM {$wpdb->site} LIMIT 1");

            return [$network ?: null, 3600];
        });
    }
}

if (!function_exists('maybe_unserialize')) {
    /**
     * Unserializes data only if it was serialized.
     */
    function maybe_unserialize(mixed $original): mixed
    {
        if (is_serialized($original)) {
            return @unserialize($original);
        }

        return $original;
    }
}

if (!function_exists('is_serialized')) {
    /**
     * Checks if data is serialized.
     */
    function is_serialized(mixed $data): bool
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);

        if ('N;' === $data) {
            return true;
        }

        if (strlen($data) < 4) {
            return false;
        }

        if (':' !== $data[1]) {
            return false;
        }

        return (bool) preg_match('/^[adObis]:/', $data);
    }
}

if (!function_exists('wp_normalize_path')) {
    /**
     * Normalizes a filesystem path.
     */
    function wp_normalize_path(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|/+|', '/', $path);

        return $path;
    }
}

if (!function_exists('wp_upload_dir')) {
    /**
     * Retrieves an array containing the current upload directory's path and URL.
     */
    function wp_upload_dir(string $time = null): array
    {
        $siteurl = get_option('siteurl');
        $upload_path = get_option('upload_path');

        if (empty($upload_path) || 'wp-content/uploads' === $upload_path) {
            $dir = WP_CONTENT_DIR . '/uploads';
        } elseif (str_starts_with($upload_path, ABSPATH)) {
            $dir = path_combine(ABSPATH, $upload_path);
        } elseif (!str_starts_with($upload_path, WP_CONTENT_DIR)) {
            $dir = WP_CONTENT_DIR . '/' . $upload_path;
        } else {
            $dir = $upload_path;
        }

        $url = str_replace(ABSPATH, $siteurl . '/', $dir);

        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }

        $basedir = $dir;
        $baseurl = $url;

        if ($time) {
            $y = substr($time, 0, 4);
            $m = substr($time, 4, 2);

            $dir = path_combine($dir, "$y/$m");
            $url = path_combine($url, "$y/$m");

            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }

        return [
            'path' => $dir,
            'url' => $url,
            'subdir' => '',
            'basedir' => $basedir,
            'baseurl' => $baseurl,
            'error' => false,
        ];
    }
}

if (!function_exists('path_combine')) {
    /**
     * Combines two path strings.
     */
    function path_combine(string $base, string $path): string
    {
        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }
}

if (!function_exists('wp_mkdir_p')) {
    /**
     * Creates a directory recursively.
     */
    function wp_mkdir_p(string $target): bool
    {
        if (is_dir($target)) {
            return true;
        }

        return mkdir($target, 0755, true);
    }
}

if (!function_exists('get_theme_root_uri')) {
    /**
     * Gets the theme root URI.
     */
    function get_theme_root_uri(string $theme = '', string $scheme = 'https'): string
    {
        $theme_root = get_theme_roots();

        if ($theme && str_contains($theme, '/')) {
            $theme = str_replace('/', '', $theme);
        }

        $uri = content_url('themes' . ($theme ? "/{$theme}" : ''));

        return set_url_scheme($uri, $scheme);
    }
}

if (!function_exists('get_theme_roots')) {
    /**
     * Gets the theme roots.
     */
    function get_theme_roots(): string
    {
        return '/themes';
    }
}
