<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress Script and Style API,
 * providing async-compatible asset enqueueing for Swoole environment.
 *
 * @package Swilen\Wp
 */

global $wp_scripts;
global $wp_styles;

if (!isset($wp_scripts)) {
    $wp_scripts = new \stdClass();
    $wp_scripts->queue = [];
    $wp_scripts->registered = [];
    $wp_scripts->done = [];
    $wp_scripts->in_footer = [];
    $wp_scripts->groups = [];
}

if (!isset($wp_styles)) {
    $wp_styles = new \stdClass();
    $wp_styles->queue = [];
    $wp_styles->registered = [];
    $wp_styles->done = [];
    $wp_styles->groups = [];
}

if (!function_exists('wp_enqueue_script')) {
    /**
     * Enqueue a script.
     */
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], array|bool|string $ver = false, bool $in_footer = false): void
    {
        global $wp_scripts;

        if ($src) {
            $wp_scripts->registered[$handle] = [
                'src' => $src,
                'deps' => $deps,
                'ver' => $ver,
                'in_footer' => $in_footer,
            ];
        }

        if (!in_array($handle, $wp_scripts->queue, true)) {
            $wp_scripts->queue[] = $handle;
        }

        if ($in_footer) {
            $wp_scripts->in_footer[] = $handle;
        }

        do_action('wp_enqueue_scripts');
    }
}

if (!function_exists('wp_enqueue_style')) {
    /**
     * Enqueue a CSS stylesheet.
     */
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], array|bool|string $ver = false, string $media = 'all'): void
    {
        global $wp_styles;

        if ($src) {
            $wp_styles->registered[$handle] = [
                'src' => $src,
                'deps' => $deps,
                'ver' => $ver,
                'media' => $media,
            ];
        }

        if (!in_array($handle, $wp_styles->queue, true)) {
            $wp_styles->queue[] = $handle;
        }

        do_action('wp_enqueue_scripts');
    }
}

if (!function_exists('wp_register_script')) {
    /**
     * Register a new script.
     */
    function wp_register_script(string $handle, string $src, array $deps = [], array|bool|string $ver = false, bool $in_footer = false): bool
    {
        global $wp_scripts;

        $wp_scripts->registered[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'in_footer' => $in_footer,
        ];

        return true;
    }
}

if (!function_exists('wp_register_style')) {
    /**
     * Register a new stylesheet.
     */
    function wp_register_style(string $handle, string $src, array $deps = [], array|bool|string $ver = false, string $media = 'all'): bool
    {
        global $wp_styles;

        $wp_styles->registered[$handle] = [
            'src' => $src,
            'deps' => $deps,
            'ver' => $ver,
            'media' => $media,
        ];

        return true;
    }
}

if (!function_exists('wp_deregister_script')) {
    /**
     * Remove a registered script.
     */
    function wp_deregister_script(string $handle): bool
    {
        global $wp_scripts;

        unset($wp_scripts->registered[$handle]);

        return true;
    }
}

if (!function_exists('wp_deregister_style')) {
    /**
     * Remove a registered stylesheet.
     */
    function wp_deregister_style(string $handle): bool
    {
        global $wp_styles;

        unset($wp_styles->registered[$handle]);

        return true;
    }
}

if (!function_exists('wp_add_inline_script')) {
    /**
     * Add extra code to a registered script.
     */
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
    {
        global $wp_scripts;

        if (!isset($wp_scripts->registered[$handle])) {
            return false;
        }

        if (!isset($wp_scripts->registered[$handle]['extra'])) {
            $wp_scripts->registered[$handle]['extra'] = [];
        }

        if (!isset($wp_scripts->registered[$handle]['extra'][$position])) {
            $wp_scripts->registered[$handle]['extra'][$position] = '';
        }

        $wp_scripts->registered[$handle]['extra'][$position] .= "\n" . $data;

        return true;
    }
}

if (!function_exists('wp_add_inline_style')) {
    /**
     * Add extra CSS to a registered stylesheet.
     */
    function wp_add_inline_style(string $handle, string $data): bool
    {
        global $wp_styles;

        if (!isset($wp_styles->registered[$handle])) {
            return false;
        }

        if (!isset($wp_styles->registered[$handle]['extra'])) {
            $wp_styles->registered[$handle]['extra'] = [];
        }

        if (!isset($wp_styles->registered[$handle]['extra']['after'])) {
            $wp_styles->registered[$handle]['extra']['after'] = [];
        }

        $wp_styles->registered[$handle]['extra']['after'][] = $data;

        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    /**
     * Localize a script.
     */
    function wp_localize_script(string $handle, string $object_name, array $l10n): bool
    {
        global $wp_scripts;

        if (!isset($wp_scripts->registered[$handle])) {
            return false;
        }

        if (!isset($wp_scripts->registered[$handle]['extra'])) {
            $wp_scripts->registered[$handle]['extra'] = [];
        }

        $wp_scripts->registered[$handle]['extra']['data'] = [
            'object_name' => $object_name,
            'l10n' => $l10n,
        ];

        return true;
    }
}

if (!function_exists('wp_script_is')) {
    /**
     * Check if a script has been enqueued.
     */
    function wp_script_is(string $handle, string $list = 'queue'): bool
    {
        global $wp_scripts;

        return in_array($handle, $wp_scripts->$list ?? [], true);
    }
}

if (!function_exists('wp_style_is')) {
    /**
     * Check if a style has been enqueued.
     */
    function wp_style_is(string $handle, string $list = 'queue'): bool
    {
        global $wp_styles;

        return in_array($handle, $wp_styles->$list ?? [], true);
    }
}

if (!function_exists('wp_dequeue_script')) {
    /**
     * Remove a previously enqueued script.
     */
    function wp_dequeue_script(string $handle): void
    {
        global $wp_scripts;

        $wp_scripts->queue = array_diff($wp_scripts->queue, [$handle]);
    }
}

if (!function_exists('wp_dequeue_style')) {
    /**
     * Remove a previously enqueued style.
     */
    function wp_dequeue_style(string $handle): void
    {
        global $wp_styles;

        $wp_styles->queue = array_diff($wp_styles->queue, [$handle]);
    }
}

if (!function_exists('wp_dequeue_scripts')) {
    /**
     * Dequeue all scripts.
     */
    function wp_dequeue_scripts(): void
    {
        global $wp_scripts;

        $wp_scripts->queue = [];
    }
}

if (!function_exists('wp_dequeue_styles')) {
    /**
     * Dequeue all styles.
     */
    function wp_dequeue_styles(): void
    {
        global $wp_styles;

        $wp_styles->queue = [];
    }
}

if (!function_exists('wp_print_scripts')) {
    /**
     * Print queued scripts.
     */
    function wp_print_scripts(): void
    {
        global $wp_scripts;

        do_action('wp_print_scripts');

        foreach ($wp_scripts->queue as $handle) {
            wp_print_script_tag($handle);
        }
    }
}

if (!function_exists('wp_print_styles')) {
    /**
     * Print queued styles.
     */
    function wp_print_styles(): void
    {
        global $wp_styles;

        do_action('wp_print_styles');

        foreach ($wp_styles->queue as $handle) {
            wp_print_style_tag($handle);
        }
    }
}

if (!function_exists('wp_print_script_tag')) {
    /**
     * Print a script tag.
     */
    function wp_print_script_tag(string $handle): void
    {
        global $wp_scripts;

        if (!isset($wp_scripts->registered[$handle])) {
            return;
        }

        $script = $wp_scripts->registered[$handle];

        $src = $script['src'];
        $ver = $script['ver'];
        $in_footer = $script['in_footer'] ?? false;

        $attr = [];

        if (!empty($script['deps'])) {
            $attr['deps'] = implode(',', $script['deps']);
        }

        if ($ver) {
            $attr['ver'] = $ver;
        }

        $html = '<script ';

        foreach ($attr as $key => $value) {
            $html .= esc_attr($key) . '="' . esc_attr($value) . '" ';
        }

        $html .= 'src="' . esc_url($src) . '" ';

        if ($in_footer) {
            $html .= '></script>' . "\n";
        } else {
            $html .= '></script>' . "\n";
        }

        if (!empty($script['extra']['before'])) {
            $html = $script['extra']['before'] . "\n" . $html;
        }

        if (!empty($script['extra']['after'])) {
            $html .= "\n" . $script['extra']['after'];
        }

        echo $html;
    }
}

if (!function_exists('wp_print_style_tag')) {
    /**
     * Print a style tag.
     */
    function wp_print_style_tag(string $handle): void
    {
        global $wp_styles;

        if (!isset($wp_styles->registered[$handle])) {
            return;
        }

        $style = $wp_styles->registered[$handle];

        $src = $style['src'];
        $ver = $style['ver'];
        $media = $style['media'] ?? 'all';

        $html = '<link rel="stylesheet" ';
        $html .= 'id="' . esc_attr($handle) . '-css" ';

        if ($ver) {
            $html .= 'href="' . esc_url($src . '?ver=' . $ver) . '" ';
        } else {
            $html .= 'href="' . esc_url($src) . '" ';
        }

        $html .= 'media="' . esc_attr($media) . '" />' . "\n";

        if (!empty($style['extra']['after'])) {
            $html .= '<style>' . implode("\n", $style['extra']['after']) . '</style>' . "\n";
        }

        echo $html;
    }
}

if (!function_exists('wp_get_script_tag')) {
    /**
     * Get a script tag.
     */
    function wp_get_script_tag(array $attributes): string
    {
        $html = '<script ';

        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= esc_attr($name) . ' ';
                }
            } else {
                $html .= esc_attr($name) . '="' . esc_attr($value) . '" ';
            }
        }

        $html .= '></script>' . "\n";

        return $html;
    }
}

if (!function_exists('wp_get_style_tag')) {
    /**
     * Get a style tag.
     */
    function wp_get_style_tag(array $attributes): string
    {
        $html = '<link rel="stylesheet" ';

        foreach ($attributes as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= esc_attr($name) . ' ';
                }
            } else {
                $html .= esc_attr($name) . '="' . esc_attr($value) . '" ';
            }
        }

        $html .= '/>' . "\n";

        return $html;
    }
}

if (!function_exists('wp_resource_hints')) {
    /**
     * Add resource hints to pages.
     */
    function wp_resource_hints(array $urls, string $relation_type): array
    {
        if ('dns-prefetch' === $relation_type) {
            return apply_filters('dns_prefetch_urls', $urls, $relation_type);
        }

        return $urls;
    }
}

if (!function_exists('wp_scripts')) {
    /**
     * Get the global $wp_scripts object.
     */
    function wp_scripts(): \stdClass
    {
        global $wp_scripts;

        return $wp_scripts;
    }
}

if (!function_exists('wp_styles')) {
    /**
     * Get the global $wp_styles object.
     */
    function wp_styles(): \stdClass
    {
        global $wp_styles;

        return $wp_styles;
    }
}

if (!function_exists('wp_add_editor_styles')) {
    /**
     * Add custom stylesheets for the visual editor.
     */
    function wp_add_editor_styles(string $style): void
    {
        add_editor_style($style);
    }
}

if (!function_exists('add_editor_style')) {
    /**
     * Add callback for custom stylesheets in the visual editor.
     */
    function add_editor_style(string $style): void
    {
        do_action('add_editor_style', $style);
    }
}

if (!function_exists('wp_head_scripts')) {
    /**
     * Print scripts that need to be in the head.
     */
    function wp_head_scripts(): void
    {
        global $wp_scripts;

        foreach ($wp_scripts->queue as $handle) {
            if (!in_array($handle, $wp_scripts->in_footer, true)) {
                wp_print_script_tag($handle);
            }
        }
    }
}

if (!function_exists('wp_footer_scripts')) {
    /**
     * Print scripts that need to be in the footer.
     */
    function wp_footer_scripts(): void
    {
        global $wp_scripts;

        foreach ($wp_scripts->queue as $handle) {
            if (in_array($handle, $wp_scripts->in_footer, true)) {
                wp_print_script_tag($handle);
            }
        }
    }
}
