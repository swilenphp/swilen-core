<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress Escaping Functions,
 * providing secure output escaping for async Swoole environment.
 *
 * @package Swilen\Wp
 */

if (!function_exists('esc_html')) {
    /**
     * Escapes HTML by converting special characters to HTML entities.
     */
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
    }
}

// if (!function_exists('esc_html__')) {
//     /**
//      * Retrieve the translated string and escape it for safe HTML output.
//      */
//     function esc_html__(string $text, string $domain = 'default'): string
//     {
//         return esc_html(translate($text, $domain));
//     }
// }

// if (!function_exists('esc_html_e')) {
//     /**
//      * Display translated text that has been escaped for safe HTML output.
//      */
//     function esc_html_e(string $text, string $domain = 'default'): void
//     {
//         echo esc_html__($text, $domain);
//     }
// }

if (!function_exists('esc_attr')) {
    /**
     * Escapes HTML attributes by converting special characters to HTML entities.
     */
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
    }
}

// if (!function_exists('esc_attr__')) {
//     /**
//      * Retrieve the translated string and escape it for safe attribute output.
//      */
//     function esc_attr__(string $text, string $domain = 'default'): string
//     {
//         return esc_attr(translate($text, $domain));
//     }
// }

// if (!function_exists('esc_attr_e')) {
//     /**
//      * Display translated text that has been escaped for safe attribute output.
//      */
//     function esc_attr_e(string $text, string $domain = 'default'): void
//     {
//         echo esc_attr__($text, $domain);
//     }
// }

if (!function_exists('esc_url')) {
    /**
     * Checks and cleans a URL.
     */
    function esc_url(?string $url, array $protocols = [], string $_context = 'display'): string
    {
        if (empty($url)) {
            return '';
        }

        $url = str_replace(' ', '%20', $url);
        $url = str_replace("\0", '', $url);

        if (preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return '';
        }

        if ($protocols === null) {
            $protocols = ['http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'irc7', 'feed', 'telnet', 'ssh', 'rlogin', 'telnet', 'gopher', 'javascript', 'data', 'xmpp', 'webcal', 'urn'];
        }

        $url = trim($url);

        if (str_starts_with($url, '//')) {
            $url = 'http:' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^data:/i', $url)) {
            return '';
        }

        $parsed = @parse_url($url);

        if ($parsed === false || empty($parsed['host'])) {
            return '';
        }

        if (!in_array($parsed['scheme'] ?? '', $protocols, true)) {
            return '';
        }

        $host = strtolower($parsed['host']);

        if (preg_match('/[^\x20-\x7E]/', $host)) {
            return '';
        }

        $url = filter_var($url, FILTER_SANITIZE_URL);

        return $url ?: '';
    }
}

if (!function_exists('esc_url_raw')) {
    /**
     * Performs esc_url() for database usage.
     */
    function esc_url_raw(?string $url, array $protocols = []): string
    {
        return esc_url($url, $protocols, 'db');
    }
}

if (!function_exists('esc_js')) {
    /**
     * Escapes single quotes, "false", newline and tab characters for JavaScript output.
     */
    function esc_js(string $text): string
    {
        $safe_text = wp_check_invalid_utf8($text);
        $safe_text = _wp_specialchars($safe_text, ENT_COMPAT);
        $safe_text = preg_replace('/&#(x)?0*(?(1telecom|g);)?/i', '', $safe_text);
        $safe_text = str_replace("\r", '', $safe_text);
        $safe_text = str_replace("\n", '\\n', $safe_text);
        $safe_text = str_replace('"', '\\"', $safe_text);
        $safe_text = str_replace("'", "\\'", $safe_text);

        return $safe_text;
    }
}

if (!function_exists('esc_textarea')) {
    /**
     * Escapes text area for safe HTML output.
     */
    function esc_textarea(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('esc_sql')) {
    /**
     * Escapes data for use in a MySQL query.
     */
    function esc_sql(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map('esc_sql', $data);
        }

        if (is_numeric($data)) {
            return $data;
        }

        if (is_null($data)) {
            return 'NULL';
        }

        global $wpdb;

        if (method_exists($wpdb, 'prepare')) {
            return $wpdb->prepare('%s', $data);
        }

        return "'" . str_replace("'", "''", $data) . "'";
    }
}

if (!function_exists('wp_check_invalid_utf8')) {
    /**
     * Checks for invalid UTF8 characters.
     */
    function wp_check_invalid_utf8(string $string, bool $strip = false): string
    {
        $string = (string) $string;

        if (strlen($string) === 0) {
            return '';
        }

        $string = preg_replace('/[\x00-\x08\x10\x11\x12\x14-\x1F\x7F]/', '', $string);

        if (preg_match('/[\xC2-\xF4]/', $string)) {
            if (!preg_match_all('/[\xC2-\xF4][\x80-\xBF]+/', $string, $matches)) {
                return $strip ? '' : $string;
            }

            $string = implode('', $matches[0]);
        }

        return $string;
    }
}

if (!function_exists('_wp_specialchars')) {
    /**
     * Converts a number of special characters into their HTML entities.
     */
    function _wp_specialchars(string $string, string $quote_style = ENT_NOQUOTES): string
    {
        $string = (string) $string;

        if (strlen($string) === 0) {
            return '';
        }

        $string = str_replace(["\r\n", "\r", "\n"], "\n", $string);

        $string = htmlspecialchars($string, $quote_style, 'UTF-8', false);

        return $string;
    }
}

if (!function_exists('tag_escape')) {
    /**
     * Escapes an HTML tag name.
     */
    function tag_escape(?string $tag): string
    {
        if (empty($tag)) {
            return '';
        }

        return preg_replace('/[^a-zA-Z0-9-_:]/', '', $tag);
    }
}

if (!function_exists('wp_kses_post')) {
    /**
     * Filter content with allowed HTML tags for post content.
     */
    function wp_kses_post(string $data): string
    {
        return wp_kses($data, wp_kses_allowed_html('post'));
    }
}

if (!function_exists('wp_kses_allowed_html')) {
    /**
     * Returns an array of allowed HTML tags.
     */
    function wp_kses_allowed_html(string $context = 'post'): array
    {
        $allowed = [
            'address' => [],
            'article' => ['class' => true, 'id' => true],
            'aside' => ['class' => true, 'id' => true],
            'footer' => ['class' => true, 'id' => true],
            'header' => ['class' => true, 'id' => true],
            'h1' => ['class' => true, 'id' => true],
            'h2' => ['class' => true, 'id' => true],
            'h3' => ['class' => true, 'id' => true],
            'h4' => ['class' => true, 'id' => true],
            'h5' => ['class' => true, 'id' => true],
            'h6' => ['class' => true, 'id' => true],
            'main' => ['class' => true, 'id' => true],
            'nav' => ['class' => true, 'id' => true],
            'section' => ['class' => true, 'id' => true],
            'p' => ['class' => true, 'id' => true],
            'br' => [],
            'em' => ['class' => true, 'id' => true],
            'strong' => ['class' => true, 'id' => true],
            'a' => ['href' => true, 'class' => true, 'id' => true, 'title' => true, 'rel' => true],
            'ul' => ['class' => true, 'id' => true],
            'ol' => ['class' => true, 'id' => true],
            'li' => ['class' => true, 'id' => true],
            'table' => ['class' => true, 'id' => true, 'border' => true],
            'thead' => [],
            'tbody' => [],
            'tfoot' => [],
            'tr' => [],
            'th' => ['scope' => true, 'class' => true],
            'td' => ['class' => true],
            'img' => ['src' => true, 'alt' => true, 'class' => true, 'id' => true, 'width' => true, 'height' => true],
            'div' => ['class' => true, 'id' => true],
            'span' => ['class' => true, 'id' => true],
            'blockquote' => ['cite' => true, 'class' => true],
            'code' => ['class' => true],
            'pre' => ['class' => true],
        ];

        return $allowed;
    }
}

if (!function_exists('wp_kses')) {
    /**
     * Filters content with allowed HTML tags.
     */
    function wp_kses(string $data, array $allowed_html, array $allowed_protocols = []): string
    {
        if (empty($allowed_protocols)) {
            $allowed_protocols = ['http', 'https', 'mailto', 'ftp'];
        }

        $data = wp_check_invalid_utf8($data);

        $data = strip_tags($data);

        return $data;
    }
}

if (!function_exists('wp_rel_nofollow')) {
    /**
     * Adds rel="nofollow" string to all HTML A tags in content.
     */
    function wp_rel_nofollow(string $text): string
    {
        return preg_replace_callback('|<a\s+([^>]*)>|i', function ($matches) {
            $text = $matches[1];
            $text = str_replace(['rel="]', "rel=']"], 'rel="nofollow]', $text);

            if (!preg_match('/rel=["\']nofollow["\']/', $text)) {
                $text = rtrim($text, '/') . ' rel="nofollow"';
            }

            return "<a $text>";
        }, $text);
    }
}
