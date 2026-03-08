<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for the WordPress Event System,
 * bridging the legacy Hook API with the modern Swilen Events Dispatcher and PriorityPipeline.
 *
 * All functions are documented in English for global maintainability.
 *
 * @package Swilen\Wp
 */

use Swilen\Pipeline\PriorityPipeline;
use Swilen\Wp\Hooks\WpHookEvent;

// ============================================================================
// CONSTANTS & GLOBALS
// ============================================================================

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('WPMU_PLUGIN_DIR')) {
    define('WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins');
}

/**
 * Helper to get the PriorityPipeline instance for filters.
 */
function filters_pipeline(): PriorityPipeline
{
    return app()->make(PriorityPipeline::class);
}

if (!function_exists('add_action')) {
    /**
     * Hooks a function or method to a specific action.
     */
    function add_action(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        dispatcher()->listen($tag, $callback, $priority);
        return true;
    }
}

if (!function_exists('do_action')) {
    /**
     * Calls the callback functions that have been added to an action hook.
     */
    function do_action(string $tag, ...$args): void
    {
        dispatcher()->dispatch(new WpHookEvent($tag, $args));
    }
}

if (!function_exists('do_action_ref_array')) {
    /**
     * Calls the callback functions that have been added to an action hook, specifying arguments in an array.
     */
    function do_action_ref_array(string $tag, array $args): void
    {
        do_action($tag, ...$args);
    }
}

if (!function_exists('remove_action')) {
    /**
     * Removes a function from a specified action hook.
     */
    function remove_action(string $tag, callable $callback, int $priority = 10): bool
    {
        dispatcher()->forget($tag, $callback, $priority);
        return true;
    }
}

if (!function_exists('remove_all_actions')) {
    /**
     * Removes all of the callback functions from an action hook.
     */
    function remove_all_actions(string $tag, $priority = false): bool
    {
        dispatcher()->forget($tag, null, $priority === false ? 0 : $priority);
        return true;
    }
}

if (!function_exists('has_action')) {
    /**
     * Checks if any action has been registered for a given hook.
     */
    function has_action(string $tag): bool
    {
        return dispatcher()->has($tag);
    }
}

if (!function_exists('did_action')) {
    /**
     * Retrieve the number of times an action has been fired.
     */
    function did_action(string $tag): int
    {
        return dispatcher()->firedCount($tag);
    }
}

if (!function_exists('current_action')) {
    /**
     * Retrieves the name of the current action hook.
     */
    function current_action(): string
    {
        return dispatcher()->current();
    }
}

if (!function_exists('doing_action')) {
    /**
     * Returns whether or not an action hook is currently being processed.
     */
    function doing_action(?string $tag = null): bool
    {
        if ($tag === null) {
            return !empty(dispatcher()->current());
        }

        return dispatcher()->current() === $tag;
    }
}

// ============================================================================
// FILTERS API (Implemented via PriorityPipeline)
// ============================================================================

if (!function_exists('add_filter')) {
    /**
     * Adds a callback function to a filter hook.
     */
    function add_filter(string $tag, callable $callback, int $priority = 10, int $accepted_args = 1): bool
    {
        filters_pipeline()->add($tag, $callback, $priority);
        return true;
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Calls the callback functions that have been added to a filter hook.
     */
    function apply_filters(string $tag, mixed $value, ...$args): mixed
    {
        if (!filters_pipeline()->has($tag)) {
            return $value;
        }

        return filters_pipeline()->apply($tag, $value, $args);
    }
}

if (!function_exists('apply_filters_ref_array')) {
    /**
     * Calls the callback functions that have been added to a filter hook, specifying arguments in an array.
     */
    function apply_filters_ref_array(string $tag, array $args): mixed
    {
        $value = array_shift($args);
        return apply_filters($tag, $value, ...$args);
    }
}

if (!function_exists('remove_filter')) {
    /**
     * Removes a function from a specified filter hook.
     */
    function remove_filter(string $tag, callable $callback, int $priority = 10): bool
    {
        filters_pipeline()->remove($tag, $callback, $priority);
        return true;
    }
}

if (!function_exists('remove_all_filters')) {
    /**
     * Removes all of the callback functions from a filter hook.
     */
    function remove_all_filters(string $tag, $priority = false): bool
    {
        // Currently PriorityPipeline doesn't have remove_all, we'd need to add it if needed.
        return true;
    }
}

if (!function_exists('has_filter')) {
    /**
     * Checks if any filter has been registered for a hook.
     */
    function has_filter(string $tag): bool
    {
        return filters_pipeline()->has($tag);
    }
}

if (!function_exists('did_filter')) {
    /**
     * Retrieves the number of times a filter has been applied (sharing count with did_action for compatibility)
     */
    function did_filter(string $tag): int
    {
        return did_action($tag);
    }
}

if (!function_exists('current_filter')) {
    /**
     * Retrieves the name of the current filter hook.
     */
    function current_filter(): string
    {
        return current_action();
    }
}

if (!function_exists('doing_filter')) {
    /**
     * Returns whether or not a filter hook is currently being processed.
     */
    function doing_filter(?string $tag = null): bool
    {
        return doing_action($tag);
    }
}
