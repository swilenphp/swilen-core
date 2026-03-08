<?php

/** Define ABSPATH as this file's directory */
if (! defined('ABSPATH')) {
    define('ABSPATH', base_path());
}

if (!defined("WP_CONTENT_DIR")) {
    define('WP_CONTENT_DIR', base_path('wp-content'));
}

if (!defined("WP_INCLUDES_DIR")) {
    define('WP_INCLUDES_DIR', base_path('wp-includes'));
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', base_path('plugins'));
}

if (!defined('WPMU_PLUGIN_DIR')) {
    define('WPMU_PLUGIN_DIR', base_path('mu-plugins'));
}
