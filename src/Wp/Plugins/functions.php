<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for the WordPress Event System,
 * bridging the legacy Hook API with the modern Swilen Events Dispatcher and PriorityPipeline.
 *
 * All functions are documented in English for global maintainability.
 * Helpers used by plugins to resolve paths and URLs.

 * - plugin_dir_path
 * - plugin_dir_url
 * - plugins_url
 * - is_plugin_active
 * - is_plugin_active_for_network
 * - register_activation_hook
 * - register_deactivation_hook
 * - register_uninstall_hook
 *
 * @package Swilen\Wp\Plugins
 */

// ============================================================================
// PLUGINS API
// ============================================================================

if (!function_exists('plugin_basename')) {
    /**
     * Gets the basename of a plugin.
     */
    function plugin_basename(string $file): string
    {
        $file = str_replace('\\', '/', $file);
        $plugin_dir = str_replace('\\', '/', WP_PLUGIN_DIR);

        if ($plugin_dir && str_starts_with($file, $plugin_dir)) {
            $file = substr($file, strlen($plugin_dir));
        }

        return trim($file, '/');
    }
}

if (!function_exists('plugin_dir_path')) {
    /**
     * Get the filesystem directory path for the plugin.
     */
    function plugin_dir_path(string $file): string
    {
        return trailingslashit(dirname($file));
    }
}

if (!function_exists('trailingslashit')) {
    /**
     * Appends a trailing slash.
     */
    function trailingslashit(string $string): string
    {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('register_activation_hook')) {
    /**
     * Set the activation hook for a plugin.
     */
    function register_activation_hook(string $file, callable $callback): void
    {
        $file = plugin_basename($file);
        add_action('activate_' . $file, $callback);
    }
}

if (!function_exists('register_deactivation_hook')) {
    /**
     * Sets the deactivation hook for a plugin.
     */
    function register_deactivation_hook(string $file, callable $callback): void
    {
        $file = plugin_basename($file);
        add_action('deactivate_' . $file, $callback);
    }
}
