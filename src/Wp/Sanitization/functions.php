<?php

/**
 * Swilen WordPress Compatibility API
 *
 * This file contains the procedural wrappers for WordPress Sanitization Functions,
 * providing input sanitization for async Swoole environment.
 *
 * @package Swilen\Wp
 */

if (!function_exists('sanitize_text_field')) {
    /**
     * Sanitizes a string from user input or from the database.
     */
    function sanitize_text_field(string $str): string
    {
        $filtered = wp_check_invalid_utf8($str);

        if (str_starts_with($filtered, 'BOM')) {
            $filtered = substr($filtered, 3);
        }

        $filtered = trim($filtered);
        $filtered = remove_accents($filtered);
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        $filtered = strip_tags($filtered);

        return $filtered;
    }
}

if (!function_exists('sanitize_email')) {
    /**
     * Sanitizes an email address.
     */
    function sanitize_email(?string $email): string
    {
        if (empty($email)) {
            return '';
        }

        $email = wp_check_invalid_utf8($email);
        $email = preg_replace('/[^a-z0-9+.!#$%&\'*\-/=?^_`{|}~@]/i', '', $email);

        return $email;
    }
}

if (!function_exists('sanitize_file_name')) {
    /**
     * Sanitizes a filename for use in storage.
     */
    function sanitize_file_name(string $filename): string
    {
        $filename = wp_check_invalid_utf8($filename);
        $filename = remove_accents($filename);
        $filename = str_replace(['%20', '+', ' '], '-', $filename);
        $filename = preg_replace('/[\r\n\t ]+/', '-', $filename);
        $filename = preg_replace('/[^\w.\-]/', '', $filename);
        $filename = preg_replace('/\.[\.]+/', '.', $filename);
        $filename = trim($filename, '.-_');

        return $filename;
    }
}

if (!function_exists('sanitize_title')) {
    /**
     * Sanitizes a title, or a part of a title.
     */
    function sanitize_title(string $title, string $fallback_title = '', string $context = 'save'): string
    {
        $title = strip_tags($title);
        $title = remove_accents($title);

        if ('save' === $context) {
            $title = strtolower($title);
            $title = preg_replace('/&.+?;/', '', $title);
            $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        }

        $title = preg_replace('/\s+/', '-', $title);
        $title = preg_replace('|-+|', '-', $title);
        $title = trim($title, '-');

        if (empty($title) && !empty($fallback_title)) {
            return sanitize_title($fallback_title, '', $context);
        }

        return $title;
    }
}

if (!function_exists('sanitize_title_for_query')) {
    /**
     * Sanitizes a title for use in a query string.
     */
    function sanitize_title_for_query(string $title): string
    {
        return sanitize_title($title, '', 'query');
    }
}

if (!function_exists('sanitize_title_with_dashes')) {
    /**
     * Sanitizes a title, replacing whitespace and a few special characters with dashes.
     */
    function sanitize_title_with_dashes(string $title): string
    {
        return sanitize_title($title, '', 'save');
    }
}

if (!function_exists('sanitize_user')) {
    /**
     * Sanitize username stripping special characters.
     */
    function sanitize_user(string $username, bool $strict = false): string
    {
        $username = strip_tags($username);
        $username = remove_accents($username);
        $username = preg_replace('/[%s]+/u', ' ', $username);

        if ($strict) {
            $username = preg_replace('/[^a-z0-9._\-]/u', '', $username);
        }

        return trim($username);
    }
}

if (!function_exists('sanitize_key')) {
    /**
     * Sanitizes a string key.
     */
    function sanitize_key(string $key): string
    {
        $key = strtolower($key);
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);

        return $key;
    }
}

if (!function_exists('sanitize_textarea_field')) {
    /**
     * Sanitize a textarea field.
     */
    function sanitize_textarea_field(string $str): string
    {
        $filtered = wp_check_invalid_utf8($str);
        $filtered = trim($filtered);

        return $filtered;
    }
}

if (!function_exists('sanitize_url')) {
    /**
     * Alias for esc_url_raw().
     */
    function sanitize_url(?string $url, array $protocols = []): string
    {
        return esc_url_raw($url, $protocols);
    }
}

if (!function_exists('sanitize_meta')) {
    /**
     * Sanitize a meta value.
     */
    function sanitize_meta(string $key, mixed $value, string $type): mixed
    {
        if (!empty($type)) {
            $function = 'sanitize_' . $type . '_meta';
            if (function_exists($function)) {
                return $function($key, $value);
            }
        }

        if (is_array($value)) {
            return array_map('sanitize_meta', array_fill(0, count($value), $key), $value, array_fill(0, count($value), $type));
        }

        return is_scalar($value) ? sanitize_text_field((string) $value) : $value;
    }
}

if (!function_exists('sanitize_option')) {
    /**
     * Sanitizes a variety of option values.
     */
    function sanitize_option(string $option, mixed $value): mixed
    {
        switch ($option) {
            case 'category_base':
            case 'tag_base':
                $value = sanitize_url($value);
                break;
            case 'permalink_structure':
            case 'category_base':
                $value = sanitize_option('permalink_structure', $value);
                break;
            case 'default_role':
                $value = sanitize_user($value, true);
                break;
            case 'blogdescription':
            case 'blogname':
                $value = sanitize_text_field($value);
                $value = wp_specialchars_decode($value, ENT_QUOTES);
                break;
            case 'blogname':
                $value = sanitize_text_field($value);
                break;
            default:
                if (is_array($value)) {
                    $value = array_map('sanitize_option', array_fill(0, count($value), $option), $value);
                } else {
                    $value = sanitize_text_field($value);
                }
        }

        return apply_filters("sanitize_option_{$option}", $value, $option);
    }
}

if (!function_exists('wp_specialchars_decode')) {
    /**
     * Converts a number of HTML entities into their special characters.
     */
    function wp_specialchars_decode(string $string, int $quote_style = ENT_NOQUOTES): string
    {
        $string = (string) $string;

        if (0 === strlen($string)) {
            return '';
        }

        return html_entity_decode($string, $quote_style, 'UTF-8');
    }
}

if (!function_exists('sanitize_html_class')) {
    /**
     * Sanitizes an HTML classname.
     */
    function sanitize_html_class(string $class, string $fallback = ''): string
    {
        $class = sanitize_html_class_impl($class);

        if ('' === $class) {
            $class = $fallback;
        }

        return $class;
    }
}

if (!function_exists('sanitize_html_class_impl')) {
    /**
     * Internal implementation of sanitize_html_class.
     */
    function sanitize_html_class_impl(string $class): string
    {
        $class = strip_tags($class);
        $class = remove_accents($class);
        $class = preg_replace('/[^a-z0-9 _-]/i', '', $class);
        $class = trim($class);
        $class = preg_replace('/\s+/', '-', $class);

        return $class;
    }
}

if (!function_exists('sanitize_mime_type')) {
    /**
     * Sanitizes a mime type.
     */
    function sanitize_mime_type(?string $mime_type): string
    {
        if (empty($mime_type)) {
            return '';
        }

        $mime_type = preg_replace('/[^a-z0-9\-+.*]/i', '', $mime_type);

        return $mime_type;
    }
}

if (!function_exists('sanitize_trackback_urls')) {
    /**
     * Sanitizes a space or comma separated list of URLs.
     */
    function sanitize_trackback_urls(string $urls): string
    {
        $urls = preg_replace('/[\r\n\t ]+/', ' ', $urls);
        $urls = explode(',', $urls);

        $urls = array_map('esc_url_raw', $urls);

        return implode("\n", $urls);
    }
}

if (!function_exists('sanitize_hex_color')) {
    /**
     * Sanitizes a hex color.
     */
    function sanitize_hex_color(?string $color): string
    {
        if (empty($color)) {
            return '';
        }

        $color = ltrim($color, '#');

        if (!preg_match('/^[a-f0-9]{3}$/i', $color) && !preg_match('/^[a-f0-9]{6}$/i', $color)) {
            return '';
        }

        return '#' . $color;
    }
}

if (!function_exists('sanitize_hex_color_no_hash')) {
    /**
     * Sanitizes a hex color without hash.
     */
    function sanitize_hex_color_no_hash(?string $color): string
    {
        $color = ltrim($color, '#');

        return sanitize_hex_color($color) ? $color : '';
    }
}

if (!function_exists('sanitize_orderby')) {
    /**
     * Sanitizes the orderby parameter.
     */
    function sanitize_orderby(string $orderby): string
    {
        $allowed = [
            'none',
            'ID',
            'author',
            'title',
            'date',
            'modified',
            'parent',
            'rand',
            'comment_count',
            'meta_value',
            'meta_value_num',
        ];

        $orderby = strtolower($orderby);

        if (!in_array($orderby, $allowed, true)) {
            $orderby = 'date';
        }

        return $orderby;
    }
}

if (!function_exists('sanitize_order')) {
    /**
     * Sanitizes the order parameter.
     */
    function sanitize_order(string $order): string
    {
        $order = strtoupper($order);

        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'DESC';
        }

        return $order;
    }
}

if (!function_exists('sanitize_boolean')) {
    /**
     * Sanitizes a boolean value.
     */
    function sanitize_boolean(mixed $bool): bool
    {
        if (is_bool($bool)) {
            return $bool;
        }

        if (is_string($bool)) {
            $bool = strtolower($bool);

            if (in_array($bool, ['true', 'yes', '1'], true)) {
                return true;
            }

            if (in_array($bool, ['false', 'no', '0'], true)) {
                return false;
            }
        }

        return (bool) $bool;
    }
}

if (!function_exists('remove_accents')) {
    /**
     * Converts all accent characters to ASCII characters.
     */
    function remove_accents(string $string): string
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }

        $chars = [
            'À' => 'A',
            'Á' => 'A',
            'Â' => 'A',
            'Ã' => 'A',
            'Ä' => 'A',
            'Å' => 'A',
            'Æ' => 'AE',
            'Ç' => 'C',
            'È' => 'E',
            'É' => 'E',
            'Ê' => 'E',
            'Ë' => 'E',
            'Ì' => 'I',
            'Í' => 'I',
            'Î' => 'I',
            'Ï' => 'I',
            'Ð' => 'D',
            'Ñ' => 'N',
            'Ò' => 'O',
            'Ó' => 'O',
            'Ô' => 'O',
            'Õ' => 'O',
            'Ö' => 'O',
            'Ø' => 'O',
            'Ù' => 'U',
            'Ú' => 'U',
            'Û' => 'U',
            'Ü' => 'U',
            'Ý' => 'Y',
            'Þ' => 'TH',
            'ß' => 'ss',
            'à' => 'a',
            'á' => 'a',
            'â' => 'a',
            'ã' => 'a',
            'ä' => 'a',
            'å' => 'a',
            'æ' => 'ae',
            'ç' => 'c',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'ì' => 'i',
            'í' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ð' => 'd',
            'ñ' => 'n',
            'ò' => 'o',
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ö' => 'o',
            'ø' => 'o',
            'ù' => 'u',
            'ú' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ý' => 'y',
            'þ' => 'th',
            'ÿ' => 'y',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Š' => 'S',
            'š' => 's',
            'Ÿ' => 'Y',
            'Ž' => 'Z',
            'ž' => 'z',
            'ƒ' => 'f',
            'Ơ' => 'O',
            'ơ' => 'o',
            'Ư' => 'U',
            'ư' => 'u',
            'Ā' => 'A',
            'ā' => 'a',
            'Ă' => 'A',
            'ă' => 'a',
            'Ą' => 'A',
            'ą' => 'a',
            'Ć' => 'C',
            'ć' => 'c',
            'Ĉ' => 'C',
            'ĉ' => 'c',
            'Ċ' => 'C',
            'ċ' => 'c',
            'Č' => 'C',
            'č' => 'c',
            'Ď' => 'D',
            'ď' => 'd',
            'Đ' => 'D',
            'đ' => 'd',
            'Ē' => 'E',
            'ē' => 'e',
            'Ĕ' => 'E',
            'ĕ' => 'e',
            'Ė' => 'E',
            'ė' => 'e',
            'Ę' => 'E',
            'ę' => 'e',
            'Ě' => 'E',
            'ě' => 'e',
            'Ĝ' => 'G',
            'ĝ' => 'g',
            'Ğ' => 'G',
            'ğ' => 'g',
            'Ġ' => 'G',
            'ġ' => 'g',
            'Ģ' => 'G',
            'ģ' => 'g',
            'Ĥ' => 'H',
            'ĥ' => 'h',
            'Ħ' => 'H',
            'ħ' => 'h',
            'Ĩ' => 'I',
            'ĩ' => 'i',
            'Ī' => 'I',
            'ī' => 'i',
            'Ĭ' => 'I',
            'ĭ' => 'i',
            'Į' => 'I',
            'į' => 'i',
            'İ' => 'I',
            'ı' => 'i',
            'Ĳ' => 'IJ',
            'ĳ' => 'ij',
            'Ĵ' => 'J',
            'ĵ' => 'j',
            'Ķ' => 'K',
            'ķ' => 'k',
            'ĸ' => 'k',
            'Ĺ' => 'L',
            'ĺ' => 'l',
            'Ļ' => 'L',
            'ļ' => 'l',
            'Ľ' => 'L',
            'ľ' => 'l',
            'Ŀ' => 'L',
            'ŀ' => 'l',
            'Ł' => 'L',
            'ł' => 'l',
            'Ń' => 'N',
            'ń' => 'n',
            'Ņ' => 'N',
            'ņ' => 'n',
            'Ň' => 'N',
            'ň' => 'n',
            'ŉ' => 'n',
            'Ŋ' => 'N',
            'ŋ' => 'n',
            'Ō' => 'O',
            'ō' => 'o',
            'Ŏ' => 'O',
            'ŏ' => 'o',
            'Ő' => 'O',
            'ő' => 'o',
            'Œ' => 'OE',
            'œ' => 'oe',
            'Ŕ' => 'R',
            'ŕ' => 'r',
            'Ŗ' => 'R',
            'ŗ' => 'r',
            'Ř' => 'R',
            'ř' => 'r',
            'Ś' => 'S',
            'ś' => 's',
            'Ŝ' => 'S',
            'ŝ' => 's',
            'Ş' => 'S',
            'ş' => 's',
            'Š' => 'S',
            'š' => 's',
            'Ţ' => 'T',
            'ţ' => 't',
            'Ť' => 'T',
            'ť' => 't',
            'Ŧ' => 'T',
            'ŧ' => 't',
            'Ũ' => 'U',
            'ũ' => 'u',
            'Ū' => 'U',
            'ū' => 'u',
            'Ŭ' => 'U',
            'ŭ' => 'u',
            'Ů' => 'U',
            'ů' => 'u',
            'Ű' => 'U',
            'ű' => 'u',
            'Ų' => 'U',
            'ų' => 'u',
            'Ŵ' => 'W',
            'ŵ' => 'w',
            'Ŷ' => 'Y',
            'ŷ' => 'y',
            'Ÿ' => 'Y',
            'Ź' => 'Z',
            'ź' => 'z',
            'Ż' => 'Z',
            'ż' => 'z',
            'Ž' => 'Z',
            'ž' => 'z',
        ];

        return strtr($string, $chars);
    }
}
